<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use App\Enums\CancellationReason;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Support\LegacyInvoiceAddress;
use App\Support\Vat;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ports the money ([[03-invoices]]). Runs after `port:users`, because every
 * legacy invoice points at a booking.
 *
 * **This port merges nothing, and that is the important part.** Every one of
 * the 569 legacy invoices becomes one invoice with one line — including the 30
 * laptop rentals, which stay their own documents rather than becoming a second
 * line on the course invoice they belong to. Line items are for invoices the
 * rework *issues*; they are not a licence to rewrite history:
 *
 * - those rental invoices were sent as their own documents, with their own
 *   numbers, and the customer has the PDF;
 * - `user_documents` holds 568 invoice PDFs that must keep matching their rows;
 * - reconciliation is row-by-row against the legacy table, and merging pairs of
 *   invoices would make all 569 uncomparable.
 *
 * So `total`, `discount`, `vat` and `grand_total` are copied verbatim onto both
 * the line and the header, and **nothing is recomputed** — not even the 6.50 on
 * the rentals, which 5-centime rounding produced and which this rework would
 * work out as 6.48. Old and new invoices legitimately differ in shape; the
 * reconciliation below expects exactly one line on every ported row.
 */
class PortInvoices extends Command
{
	protected $signature = 'port:invoices {--dry-run : Report what would change without writing}';

	protected $description = 'Port invoices from the legacy database, one line per invoice, recomputing nothing';

	/** @var array<int, string> */
	private array $findings = [];

	/** @var array<int, string> Known and decided; reported, but not as work. */
	private array $accepted = [];

	/** @var array<int, string> Odd data, settled handling. */
	private array $observations = [];

	public function handle(): int
	{
		$legacy = DB::connection('legacy');

		if (Booking::count() === 0) {
			$this->components->error('No bookings in the target database. Run `port:users` first — every legacy invoice points at one.');

			return self::FAILURE;
		}

		if ($this->option('dry-run')) {
			$this->reconcile($legacy);

			return self::SUCCESS;
		}

		// Unguarded for the reason given in [[PortUsers]]: `uuid` is the public
		// route key and deliberately not fillable, but it has to carry across
		// unchanged or every emailed invoice link breaks on cutover day.
		Model::unguarded(function () use ($legacy): void {
			DB::transaction(function () use ($legacy): void {
				$this->clear();
				$this->portInvoices($legacy);
				$this->linkReplacements($legacy);
			});
		});

		$this->reconcile($legacy);

		return self::SUCCESS;
	}

	/** Deletes rather than truncates, so a failed port does not half-apply. */
	private function clear(): void
	{
		DB::table('invoice_items')->delete();
		DB::table('invoices')->delete();
	}

	private function portInvoices($legacy): void
	{
		// Bookings carry their legacy uuid, which is the only stable join
		// between the two ports — auto-increment ids are not preserved.
		$bookingsByUuid = Booking::withTrashed()->get(['id', 'uuid', 'user_id', 'event_id'])->keyBy('uuid');
		$legacyBookings = $legacy->table('bookings')->pluck('uuid', 'id');

		// Users were matched on email by [[PortUsers]] but kept their legacy
		// uuid, so that is the join here too.
		$usersByUuid = User::withTrashed()->pluck('id', 'uuid');
		$legacyUsers = $legacy->table('users')->pluck('uuid', 'id');

		foreach ($legacy->table('invoices')->orderBy('id')->get() as $row) {
			$bookingUuid = $legacyBookings[$row->booking_id] ?? null;
			$booking = $bookingUuid === null ? null : ($bookingsByUuid[$bookingUuid] ?? null);

			if ($booking === null) {
				$this->findings[] = "invoice {$row->number}: booking {$row->booking_id} was not ported; skipped";

				continue;
			}

			$invoice = new Invoice;

			$invoice->forceFill([
				'uuid' => $row->uuid,
				'number' => $row->number,
				// The invoice's own user, not the booking's. They agree on all
				// 569 rows; if they ever did not, the invoice is the document
				// that was sent and it wins.
				'user_id' => $this->userIdFor($row, $booking, $usersByUuid, $legacyUsers),
				'status' => InvoiceStatus::from($row->status),
				'date' => $row->date,
				// Verbatim, knowingly wrong on every row — see the finding
				// below and `Todo.md`. Deciding it here would bury it.
				'due_at' => $row->due_at,
				'paid_at' => $row->paid_at,
				'cancelled_at' => $row->cancelled_at,
				'cancellation_reason' => $this->cancellationReason($row),
				'invoice_address' => LegacyInvoiceAddress::parse($row->invoice_address),
				'net' => $row->total,
				'discount' => $row->discount ?? 0,
				'vat' => $row->vat,
				'grand_total' => $row->grand_total,
				'filename' => $row->filename,
				'created_at' => $row->created_at,
				'updated_at' => $row->updated_at,
				// The 8 soft-deleted invoices come across still soft-deleted.
				// Same rule as the events and discount codes in [[PortUsers]],
				// plus one of its own: their numbers must stay taken, or the
				// next invoice this system issues reuses a number that is on a
				// document somebody already has.
				'deleted_at' => $row->deleted_at,
			])->save();

			$this->portLine($invoice, $row, $booking);
		}
	}

	/**
	 * The single line. `is_rental` becomes a RENTAL line, everything else a
	 * COURSE line, and the amounts are the invoice's own.
	 */
	private function portLine(Invoice $invoice, object $row, Booking $booking): void
	{
		$isRental = (bool) $row->is_rental;

		$item = new InvoiceItem;

		$item->forceFill([
			'invoice_id' => $invoice->id,
			'type' => $isRental ? InvoiceItemType::Rental : InvoiceItemType::Course,
			'itemable_type' => $booking->getMorphClass(),
			'itemable_id' => $booking->id,
			// Composed from what the course is called *now*, because legacy had
			// no description column at all — the PDF derived the text at render
			// time from the live course title. So a ported description can
			// differ from the printed one wherever a course was renamed. The
			// PDF in `user_documents` is the document that was sent; this is a
			// readable label for a row in a list, and the port is where that
			// distinction stops being invisible.
			'description' => $this->description($booking, $isRental),
			'reference' => $this->reference($booking),
			'position' => 1,
			'net' => $row->total,
			'discount' => $row->discount ?? 0,
			// The rate that was in force, not a rate derived from the amounts:
			// 8.1 % on a rental, exempt on a course. `vat` itself is copied,
			// so the stored 6.50 stays 6.50 even though 8.1 % of 80.00 is 6.48
			// under this rework's centime rounding ([[Vat]]).
			'vat_rate' => $isRental ? InvoiceItemType::Rental->vatRate() : '0.00',
			'vat' => $row->vat,
			'total' => $row->grand_total,
			'created_at' => $row->created_at,
			'updated_at' => $row->updated_at,
		])->save();
	}

	private function description(Booking $booking, bool $isRental): string
	{
		$event = $booking->event;
		$title = $event?->course?->title ?? 'Kurs';

		// Both strings are what the legacy PDFs print, kept so a ported row
		// reads like the document it stands for. A *newly issued* rental line
		// says "Laptopmiete" and sits on the course invoice instead.
		return $isRental
			? 'Mietcomputer für '.$title
			: $title.($event ? ', '.$event->dateRange() : '');
	}

	private function reference(Booking $booking): ?string
	{
		return $booking->event?->course ? $booking->event->number() : null;
	}

	/**
	 * The invoice's own user, not the booking's.
	 *
	 * They agree on all 569 rows, and if they ever stopped agreeing the
	 * invoice would win — it is the document that was sent, and the address on
	 * it is who was asked to pay. A disagreement is reported rather than
	 * quietly resolved.
	 *
	 * @param  Collection<string, int>  $usersByUuid
	 * @param  Collection<int, string>  $legacyUsers
	 */
	private function userIdFor(object $row, Booking $booking, $usersByUuid, $legacyUsers): int
	{
		$uuid = $legacyUsers[$row->user_id] ?? null;
		$userId = $uuid === null ? null : ($usersByUuid[$uuid] ?? null);

		if ($userId === null) {
			$this->findings[] = "invoice {$row->number}: user {$row->user_id} not found; billed to the booking's user instead";

			return $booking->user_id;
		}

		if ($userId !== $booking->user_id) {
			$this->observations[] = "invoice {$row->number}: addressed to a different user than its booking; the invoice wins";
		}

		return $userId;
	}

	private function cancellationReason(object $row): ?CancellationReason
	{
		$reason = CancellationReason::fromLegacyText($row->cancel_reason);

		if ($reason === null && filled($row->cancel_reason)) {
			$this->findings[] = "invoice {$row->number}: cancel_reason '{$row->cancel_reason}' matched no known reason; dropped";
		}

		return $reason;
	}

	/**
	 * Turns "Replaced by Invoice No. 000182" into a link.
	 *
	 * Runs as a second pass because a replacement can point forward. Six rows
	 * in the dump, all from the late-cancellation penalty flow: the original
	 * invoice is cancelled and a new one for the penalty takes its place.
	 */
	private function linkReplacements($legacy): void
	{
		$ids = Invoice::withTrashed()->pluck('id', 'number');

		$rows = $legacy->table('invoices')
			->where('cancel_reason', 'LIKE', 'Replaced by Invoice No.%')
			->get(['number', 'cancel_reason']);

		foreach ($rows as $row) {
			preg_match('/(\d{6})/', $row->cancel_reason, $matches);
			$replacement = $matches[1] ?? null;

			if ($replacement === null || ! isset($ids[$replacement])) {
				$this->findings[] = "invoice {$row->number}: says it was replaced by '{$row->cancel_reason}' but that invoice was not found; link dropped";

				continue;
			}

			Invoice::withTrashed()->where('number', $row->number)
				->update(['replaced_by_invoice_id' => $ids[$replacement]]);
		}
	}

	private function reconcile($legacy): void
	{
		$this->newLine();
		$this->components->info('Reconciliation');

		$this->reportCounts($legacy);
		$this->reportRowByRow($legacy);
		$this->reportLineCounts();
		$this->reportDueDates($legacy);
		$this->reportRoundedVat();
		$this->reportFullyDiscounted();
		$this->reportNegativeInvoices();
		$this->reportUnbilledRentals($legacy);

		$this->report();
	}

	private function reportCounts($legacy): void
	{
		$legacyTotals = $legacy->table('invoices')->selectRaw(
			'COUNT(*) c, SUM(total) net, SUM(IFNULL(discount,0)) discount, SUM(vat) vat, SUM(grand_total) grand'
		)->first();

		$ported = Invoice::withTrashed()->selectRaw(
			'COUNT(*) c, SUM(net) net, SUM(discount) discount, SUM(vat) vat, SUM(grand_total) grand'
		)->first();

		$rows = [
			['invoices', $legacyTotals->c, $ported->c],
			['invoice_items', $legacyTotals->c, InvoiceItem::count()],
			['Σ net', number_format((float) $legacyTotals->net, 2), number_format((float) $ported->net, 2)],
			['Σ discount', number_format((float) $legacyTotals->discount, 2), number_format((float) $ported->discount, 2)],
			['Σ vat', number_format((float) $legacyTotals->vat, 2), number_format((float) $ported->vat, 2)],
			['Σ grand_total', number_format((float) $legacyTotals->grand, 2), number_format((float) $ported->grand, 2)],
		];

		$this->table(
			['', 'legacy', 'ported', ''],
			array_map(fn (array $row) => [...$row, (string) $row[1] === (string) $row[2] ? 'ok' : 'MISMATCH'], $rows),
		);
	}

	/**
	 * The point of the whole command: every legacy invoice compared to its
	 * ported self, field by field. Anything that differs is a finding, because
	 * an invoice that changed in the port is an invoice that no longer matches
	 * the PDF the customer holds.
	 */
	private function reportRowByRow($legacy): void
	{
		$ported = Invoice::withTrashed()->get()->keyBy('number');
		$mismatched = 0;

		foreach ($legacy->table('invoices')->orderBy('id')->get() as $row) {
			$invoice = $ported[$row->number] ?? null;

			if ($invoice === null) {
				$this->findings[] = "invoice {$row->number}: missing from the ported data";

				continue;
			}

			$differences = [];

			foreach ([
				'net' => $row->total,
				'discount' => $row->discount ?? '0.00',
				'vat' => $row->vat,
				'grand_total' => $row->grand_total,
			] as $column => $expected) {
				if (bccomp((string) $invoice->{$column}, (string) $expected, 2) !== 0) {
					$differences[] = "{$column} {$expected} → {$invoice->{$column}}";
				}
			}

			if ($invoice->status->value !== $row->status) {
				$differences[] = "status {$row->status} → {$invoice->status->value}";
			}

			if ($differences !== []) {
				$mismatched++;
				$this->findings[] = "invoice {$row->number}: ".implode(', ', $differences);
			}
		}

		if ($mismatched === 0) {
			$this->components->info('Every legacy invoice matches its ported row, amount for amount.');
		}
	}

	/** Exactly one line per ported invoice. Two would mean a merge happened. */
	private function reportLineCounts(): void
	{
		$wrong = DB::table('invoice_items')
			->select('invoice_id', DB::raw('COUNT(*) c'))
			->groupBy('invoice_id')
			->having('c', '!=', 1)
			->pluck('c', 'invoice_id');

		foreach ($wrong as $invoiceId => $count) {
			$number = Invoice::withTrashed()->find($invoiceId)?->number ?? $invoiceId;
			$this->findings[] = "invoice {$number}: {$count} lines; the port must produce exactly one";
		}
	}

	/**
	 * `due_at` is carried across knowingly wrong, because it is wrong in the
	 * source on every row and repairing it is a decision nobody has made yet
	 * (`Todo.md`, and question 10 in `Open-Questions.md`).
	 *
	 * Both checks look for the *signature* of the self-overwriting column
	 * rather than for a particular date, so they still work on a fresher dump:
	 * a deadline that equals the moment the row was last written is not a
	 * deadline anybody set.
	 */
	private function reportDueDates($legacy): void
	{
		$paidWithLostDeadline = $legacy->table('invoices')
			->where('status', 'PAID')
			->whereRaw('ABS(TIMESTAMPDIFF(HOUR, due_at, paid_at)) <= 2')
			->count();

		$pending = $legacy->table('invoices')
			->whereIn('status', ['OPEN', 'OVERDUE'])
			->whereRaw('DATE(due_at) = DATE(updated_at)')
			->orderBy('date')
			->get(['number', 'status', 'date', 'due_at']);

		if ($paidWithLostDeadline > 0) {
			$this->findings[] = "due_at: {$paidWithLostDeadline} paid invoices have a deadline within 2h of their payment — the original is gone, overwritten by MySQL's implicit ON UPDATE. Ported verbatim; decide before cutover whether to reconstruct (Todo.md)";
		}

		if ($pending->isNotEmpty()) {
			$oldest = $pending->first();

			$this->findings[] = sprintf(
				'due_at: %d open/overdue invoices carry a deadline equal to the day they were last touched, because the column bumped itself on every write — so none of them could ever fall due. Oldest is %s, dated %s and still %s. These are live money: recompute from the event start, or carry them over knowingly wrong. Pick one, in the port (Todo.md)',
				$pending->count(),
				$oldest->number,
				$oldest->date,
				$oldest->status,
			);
		}
	}

	/**
	 * The 29 rentals whose VAT came from 5-centime rounding. Reported so that
	 * nobody "fixes" them, and so that any reconciliation recalculating VAT
	 * from `net` knows in advance which rows it will flag.
	 */
	private function reportRoundedVat(): void
	{
		$items = InvoiceItem::where('type', InvoiceItemType::Rental)->get();
		$differing = $items->filter(
			fn (InvoiceItem $item) => bccomp((string) $item->vat, Vat::on($item->taxableAmount(), (string) $item->vat_rate), 2) !== 0
		);

		if ($differing->isEmpty()) {
			return;
		}

		$this->observations[] = sprintf(
			'%d rental lines keep a VAT figure this rework would compute differently (e.g. %s: stored %s, centime rounding gives %s) — legacy rounded to 0.05. What the customer was billed stands; nothing is recomputed.',
			$differing->count(),
			$differing->first()->invoice->number,
			$differing->first()->vat,
			Vat::on($differing->first()->taxableAmount(), (string) $differing->first()->vat_rate),
		);
	}

	/** A 100 % discount code still produces a document. 16 of them do. */
	private function reportFullyDiscounted(): void
	{
		$count = Invoice::withTrashed()->where('grand_total', '0.00')->count();

		if ($count > 0) {
			$this->observations[] = "{$count} invoices have a grand total of 0.00 — a fully discounted course, or a fee of zero. They are real documents with real numbers and stay as they are.";
		}
	}

	/**
	 * The one invoice in the books that owes the customer money.
	 *
	 * Booking 000512 spent a fixed CHF 648 code on a CHF 499 course, because
	 * legacy clamped the discount at nothing; invoice 000419 has a grand total
	 * of −149.00 and is still OPEN. Ported verbatim like everything else — it
	 * is what the books say — and reported here because it is live money that
	 * needs a human, not a migration rule. Chunk 06's draw-down makes it
	 * unrepresentable from cutover on ([[06-bookings]]).
	 */
	private function reportNegativeInvoices(): void
	{
		$negative = Invoice::withTrashed()->where('grand_total', '<', 0)->get(['number', 'status', 'grand_total']);

		foreach ($negative as $invoice) {
			$this->findings[] = "invoice {$invoice->number}: grand total {$invoice->grand_total} and status {$invoice->status->value} — a discount larger than the fee, ported verbatim. Live money: needs a human before cutover (06-bookings.md)";
		}
	}

	/**
	 * Bookings that took a laptop and were never billed for it. All of them
	 * are cancelled or sit on an unconfirmed event, which is the confirmation
	 * rule working, not a gap — but the count is worth seeing, because after
	 * cutover a rental rides on the course invoice and this list should stop
	 * being possible.
	 */
	private function reportUnbilledRentals($legacy): void
	{
		$rows = $legacy->table('bookings as b')
			->leftJoin('invoices as i', function ($join): void {
				$join->on('i.booking_id', '=', 'b.id')->where('i.is_rental', 1);
			})
			->join('events as e', 'e.id', '=', 'b.event_id')
			->where('b.has_rental', 1)
			->whereNull('b.deleted_at')
			->whereNull('i.id')
			->selectRaw('COUNT(*) c, SUM(b.cancelled_at IS NOT NULL) cancelled')
			->first();

		if ($rows->c > 0) {
			$this->observations[] = "{$rows->c} bookings took a laptop and have no rental invoice ({$rows->cancelled} of them cancelled, the rest on events that were never confirmed). Nothing to bill: the rule is invoice-on-confirmation.";
		}
	}

	private function report(): void
	{
		foreach ([
			['observation(s) — the data is odd, the handling is settled:', $this->observations, 'info'],
			['known and accepted:', $this->accepted, 'info'],
		] as [$label, $notes, $style]) {
			if ($notes === []) {
				continue;
			}

			$this->newLine();
			$this->components->{$style}(count($notes).' '.$label);

			foreach ($notes as $note) {
				$this->line('  - '.$note);
			}
		}

		if ($this->findings === []) {
			$this->newLine();
			$this->components->info('No data-quality findings.');

			return;
		}

		$this->newLine();
		$this->components->warn(count($this->findings).' finding(s) needing a decision before cutover:');

		foreach ($this->findings as $finding) {
			$this->line('  - '.$finding);
		}
	}
}
