<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Documents\InvoiceDocument;
use App\Mail\BookingCancelledCustomer;
use App\Mail\BookingCancelledWithPenalty;
use App\Models\Booking;
use App\Support\CancellationPenalty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * The student's *Annullationsbestätigung* ([[10-mail]]): with the cost when the
 * cancellation came late, without it otherwise — legacy's `BookingCancelled`
 * and `BookingCancelledWithPenalty`.
 *
 * **Read, not decided.** Whether there is a cost is [[CancellationPenalty]]'s
 * answer on the day the booking was cancelled, the same answer [[CancelBooking]]
 * billed by; the invoice is whatever that left standing. A cost already
 * covered by a paid invoice is still named, with *nothing more to do*, as
 * legacy's mail had it.
 */
class SendCancellationConfirmation implements ShouldQueue
{
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;

	public function __construct(public readonly Booking $booking)
	{
		$this->afterCommit();
	}

	public function handle(CancellationPenalty $penalty, InvoiceDocument $pdf): void
	{
		$booking = $this->booking->loadMissing(['user', 'event.course', 'event.dates']);
		$on = $booking->cancelled_at;

		if (! $booking->cancellation_reason?->chargesPenalty() || ! $penalty->applies($booking, $on)) {
			Mail::to($booking->user)->send(new BookingCancelledCustomer($booking));

			return;
		}

		$invoice = $booking->invoice();
		$paid = (bool) $invoice?->isPaid();

		Mail::to($booking->user)->send(new BookingCancelledWithPenalty(
			booking: $booking,
			amount: $penalty->amount($booking, $on),
			percent: (int) round((float) $penalty->rate($booking, $on) * 100),
			paid: $paid,
			invoice: $invoice && ! $paid ? $pdf->execute($invoice) : null,
		));
	}
}
