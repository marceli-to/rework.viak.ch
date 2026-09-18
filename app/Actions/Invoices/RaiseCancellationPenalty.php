<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\CancellationReason;
use App\Enums\InvoiceItemType;
use App\Models\Booking;
use App\Models\Invoice;
use App\Support\CancellationPenalty;
use App\Support\InvoiceLine;

/**
 * Bills a late cancellation ([[06-bookings]]).
 *
 * The rule fires automatically — Marcel, 2026-09-17 — and the production data
 * agrees it always did. Fourteen student cancellations qualified and all
 * fourteen were handled correctly:
 *
 * | | Bookings | |
 * |---|---:|---|
 * | Penalty invoice raised at cancellation | 10 | at the right rate every time |
 * | Already paid in full, left alone | 4 | all inside the 100 % window, so the fee already paid *was* the penalty |
 * | — of the ten, later cancelled by a human | 2 | 000148 after three days, 000286 after five |
 *
 * So there is no gap to close, and nothing here needs to be cleverer than
 * legacy. What it needs is to be *stated*, because legacy's version was three
 * branches spread across two classes that never called each other.
 *
 * ## One correction worth not repeating
 *
 * These penalties were first counted by `cancel_reason LIKE 'Replaced by%'`,
 * which finds only a penalty that **replaced** an existing invoice. Ten of the
 * fourteen had no invoice yet — the course had not been billed when the student
 * dropped out — so their penalty was raised fresh and carried no such reason.
 * **Detect a penalty invoice by its amount and its date, not by the
 * cancellation reason on some other row.**
 */
class RaiseCancellationPenalty
{
	public function __construct(
		private readonly IssueInvoice $issue,
		private readonly CancelInvoice $cancel,
		private readonly CancellationPenalty $penalty,
	) {}

	/** Returns the penalty invoice, or null where nothing is owed. */
	public function execute(Booking $booking): ?Invoice
	{
		$booking->loadMissing('event.course', 'event.dates', 'user');

		$amount = $this->penalty->amount($booking);

		if (bccomp($amount, '0.00', 2) <= 0) {
			return null;
		}

		$existing = $this->liveInvoice($booking);

		if ($existing !== null && ! $this->shouldReplace($existing, $amount)) {
			// Paid, and for at least what the penalty comes to. The money is
			// already in — raising a second document would bill it twice.
			//
			// The case where someone paid in full and then cancelled inside the
			// *50 %* window would leave them overpaid. It has never happened in
			// 710 bookings — all four already-paid late cancellations sat in the
			// 100 % window — and Marcel decided on 2026-09-17 that VIAK corrects
			// it by hand if it ever does. No credit-note flow is built, on
			// purpose: it would be building for nothing.
			return null;
		}

		$invoice = $this->issue->execute(
			user: $booking->user,
			lines: [new InvoiceLine(
				// A penalty for a course is still education, and exempt. The
				// rate follows the thing sold, not the reason for the bill.
				type: InvoiceItemType::Course,
				description: $this->description($booking),
				net: $amount,
				reference: $booking->event->number(),
				itemable: $booking,
			)],
		);

		// Replacing, not merely raising: the original document is withdrawn and
		// points at the one that supersedes it, so an admin follows a link
		// instead of searching for a number in a sentence.
		if ($existing !== null) {
			$this->cancel->execute($existing, CancellationReason::Replaced, $invoice);
		}

		return $invoice;
	}

	/**
	 * The invoice this booking is already billed on, if any. Cancelled ones do
	 * not count — a withdrawn document left the seat still owing.
	 */
	private function liveInvoice(Booking $booking): ?Invoice
	{
		return $booking->invoiceItems()
			->with('invoice')
			->get()
			->pluck('invoice')
			->filter(fn (?Invoice $invoice) => $invoice !== null && ! $invoice->isCancelled())
			->sortByDesc('id')
			->first();
	}

	/** An unpaid invoice is replaced; a paid one that covers the penalty is not. */
	private function shouldReplace(Invoice $existing, string $amount): bool
	{
		if (! $existing->isPaid()) {
			return true;
		}

		return bccomp((string) $existing->grand_total, $amount, 2) < 0;
	}

	private function description(Booking $booking): string
	{
		$rate = $this->penalty->rate($booking);
		$percent = rtrim(rtrim(bcmul($rate, '100', 2), '0'), '.');

		return "Annullationskosten ({$percent} %), "
			.$booking->event->course->title.', '.$booking->event->dateRange();
	}
}
