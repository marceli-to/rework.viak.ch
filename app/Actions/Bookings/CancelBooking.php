<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Actions\Invoices\CancelInvoice;
use App\Actions\Invoices\RaiseCancellationPenalty;
use App\Enums\BookingCancellationReason;
use App\Enums\CancellationReason;
use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * Gives up a seat ([[06-bookings]]).
 *
 * ## One path, with the reason as data
 *
 * Legacy had two paths that shared no code. `Booking::cancel()` applied the
 * penalty; `EventCancelledHandler` flagged the rows directly and never went near
 * it. The distinction between *a student dropped out* and *VIAK called the
 * course off* existed only because the two happened not to share a function —
 * nothing stated it, and it was one refactor from vanishing.
 *
 * The cost of losing it is measurable: **115 of the 143 bookings on
 * VIAK-cancelled courses fall inside the 0–10 day window**, so a merge without
 * this distinction would invoice 115 students the full fee for a course that
 * never ran. Here the reason is written on the row and the penalty reads it
 * ([[BookingCancellationReason]]).
 *
 * ## The penalty is automatic, and waiving it is a separate human act
 *
 * Confirmed against production on 2026-09-17: the rule fired all fourteen times
 * it applied, at the right rate every time. Ten produced a penalty invoice and
 * four were already paid in full inside the 100 % window, where the fee already
 * on the paid invoice *was* the penalty. Two of the ten were later cancelled by
 * a human — which is VIAK's call to make, afterwards, and is now recorded as
 * such ([[CancellationReason]]).
 *
 * So the penalty is **not** a listener. It is money, and money is called
 * directly from the Action.
 */
class CancelBooking
{
	public function __construct(
		private readonly RaiseCancellationPenalty $penalty,
		private readonly CancelInvoice $cancelInvoice,
	) {}

	/**
	 * Returns the penalty invoice where one was raised.
	 *
	 * Idempotent: cancelling an already-cancelled booking does nothing and
	 * charges nothing. Worth guaranteeing, because `EventCancelledHandler`'s
	 * successor sweeps every booking on a called-off course and some of them
	 * will already be gone.
	 */
	public function execute(Booking $booking, BookingCancellationReason $reason): ?Invoice
	{
		if ($booking->isCancelled()) {
			return null;
		}

		DB::transaction(function () use ($booking, $reason): void {
			$booking->forceFill([
				'cancelled_at' => now(),
				'cancellation_reason' => $reason,
			])->save();
		});

		// Outside the transaction: raising a penalty issues an invoice, which
		// posts to the books over the network, and that must not hold a lock
		// open or roll back a cancellation the student has already been told
		// about ([[IssueInvoice]]).
		$penalty = $reason->chargesPenalty()
			? $this->penalty->execute($booking)
			: null;

		if ($penalty === null) {
			$this->withdrawUnpaidInvoice($booking);
		}

		event(new BookingCancelled($booking->refresh()));

		return $penalty;
	}

	/**
	 * Nothing is owed, so nothing should still be demanded.
	 *
	 * Reached when the cancellation was free — VIAK called the course off, or
	 * the student left more than 20 days out — and the seat had already been
	 * invoiced because the event was confirmed first. Legacy did the same and
	 * left the trail: 8 of the 14 cancelled invoices carry "Invoice deleted
	 * because of cancelled booking".
	 *
	 * **Paid invoices are left alone.** Money that has arrived is not withdrawn
	 * by cancelling the document it arrived against; refunding is a human act
	 * with a human record, and Marcel decided on 2026-09-17 that VIAK handles
	 * those by hand rather than through a credit-note flow that has never once
	 * been needed.
	 *
	 * A penalty run that *replaced* the invoice never gets here, because it
	 * returns the replacement.
	 */
	private function withdrawUnpaidInvoice(Booking $booking): void
	{
		$booking->invoiceItems()->with('invoice')->get()
			->pluck('invoice')
			->filter(fn (?Invoice $invoice) => $invoice !== null && ! $invoice->isCancelled() && ! $invoice->isPaid())
			->unique('id')
			->each(fn (Invoice $invoice) => $this->cancelInvoice->execute($invoice, CancellationReason::BookingCancelled));
	}
}
