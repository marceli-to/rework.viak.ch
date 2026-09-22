<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CancellationReason;
use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A bill that was sent ([[03-invoices]]).
 *
 * Two things follow from that sentence and govern this whole class:
 *
 * 1. **It is a document, not a view of the data.** `net`, `discount`, `vat` and
 *    `grand_total` are stored sums of the lines, and each line's `description`
 *    is frozen at issue. A course renamed in 2027, a fee raised, a rental price
 *    changed — none of them may retitle or re-price an invoice from 2024.
 * 2. **It covers what became billable at the same moment.** Invoices are raised
 *    when an event is *confirmed*, not at checkout, so two courses booked
 *    together but confirmed three weeks apart are two invoices. A booking with
 *    a laptop rental, confirmed in one go, is one invoice with two lines.
 *
 * There is deliberately no `booking_id`: the link to what was sold lives on
 * the line, which is how a licence — bought by anyone, with no booking at all —
 * shares this table with a course.
 */
class Invoice extends Model
{
	use HasFactory;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = [
		'number', 'user_id', 'status', 'date', 'due_at',
		'paid_at', 'cancelled_at', 'cancellation_reason', 'replaced_by_invoice_id',
		'invoice_address', 'net', 'discount', 'vat', 'grand_total', 'filename',
	];

	protected function casts(): array
	{
		return [
			'status' => InvoiceStatus::class,
			'cancellation_reason' => CancellationReason::class,
			'date' => 'date',
			'due_at' => 'date',
			'paid_at' => 'datetime',
			'cancelled_at' => 'datetime',
			'invoice_address' => 'array',
			'net' => 'decimal:2',
			'discount' => 'decimal:2',
			'vat' => 'decimal:2',
			'grand_total' => 'decimal:2',
		];
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function items(): HasMany
	{
		return $this->hasMany(InvoiceItem::class)->orderBy('position');
	}

	/** The invoice that took this one's place — the penalty flow, 6 rows. */
	public function replacedBy(): BelongsTo
	{
		return $this->belongsTo(self::class, 'replaced_by_invoice_id');
	}

	public function replaces(): HasOne
	{
		return $this->hasOne(self::class, 'replaced_by_invoice_id');
	}

	/**
	 * The frozen billing address, as lines to print ([[03-invoices]]).
	 *
	 * **The column holds two shapes and always will.** Addresses captured from
	 * the rework's own checkout are the structured snapshot
	 * `UserAddress::toSnapshot()` writes; the **131 historical invoices** carry
	 * `{"lines": [...]}`, because legacy stored a rendered HTML fragment and
	 * there is no reliable way back to fields from one ([[LegacyInvoiceAddress]]).
	 *
	 * So this reads either and never guesses. An invoice is a document that was
	 * sent: what it says has to be what was printed, and for those 131 the
	 * printed text is all there is.
	 *
	 * @return array<int, string>
	 */
	public function billingLines(): array
	{
		$address = $this->invoice_address;

		if (blank($address)) {
			return [];
		}

		if (isset($address['lines'])) {
			return array_values(array_filter((array) $address['lines'], 'filled'));
		}

		$name = trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? ''));

		return array_values(array_filter([
			$address['company'] ?? null,
			$name,
			trim(($address['street'] ?? '').' '.($address['street_no'] ?? '')),
			trim(($address['zip'] ?? '').' '.($address['city'] ?? '')),
			// The country only where it is not Switzerland, as everywhere else
			// on the site ([[UserAddress::lines]]).
			($address['country_code'] ?? 'ch') === 'ch' ? null : strtoupper((string) $address['country_code']),
		], 'filled'));
	}

	public function isPaid(): bool
	{
		return $this->status === InvoiceStatus::Paid;
	}

	public function isCancelled(): bool
	{
		return $this->status === InvoiceStatus::Cancelled;
	}

	public function isPending(): bool
	{
		return $this->status->isPending();
	}

	/** Still owed. What the admin worklist and any dunning start from. */
	public function scopePending(Builder $query): void
	{
		$query->whereIn('status', [InvoiceStatus::Open, InvoiceStatus::Overdue]);
	}

	public function scopeInStatus(Builder $query, InvoiceStatus $status): void
	{
		$query->where('status', $status);
	}

	/**
	 * Pending and past its deadline. Note the `whereDate` against a real
	 * deadline: this question was unanswerable in legacy, where `due_at`
	 * rewrote itself to today on every write, so nothing could ever be late.
	 */
	public function scopeOverdueOn(Builder $query, \DateTimeInterface $date): void
	{
		$query->pending()->whereNotNull('due_at')->whereDate('due_at', '<', $date);
	}

	/**
	 * Adds the lines up and stores the result.
	 *
	 * Called once, by the action that issues the invoice, and never again: the
	 * stored totals are the document. If a line ever has to change, that is a
	 * new invoice replacing this one ([[CancellationReason]]), which is what
	 * legacy's penalty flow did and the only honest way to correct a bill
	 * somebody already holds.
	 */
	public function storeTotalsFromItems(): void
	{
		$items = $this->items()->get();

		$this->forceFill([
			'net' => $items->reduce(fn (string $sum, InvoiceItem $item) => bcadd($sum, (string) $item->net, 2), '0.00'),
			'discount' => $items->reduce(fn (string $sum, InvoiceItem $item) => bcadd($sum, (string) $item->discount, 2), '0.00'),
			'vat' => $items->reduce(fn (string $sum, InvoiceItem $item) => bcadd($sum, (string) $item->vat, 2), '0.00'),
			'grand_total' => $items->reduce(fn (string $sum, InvoiceItem $item) => bcadd($sum, (string) $item->total, 2), '0.00'),
		])->save();
	}
}
