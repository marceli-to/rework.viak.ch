<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Documents\InvoiceDocument;
use App\Actions\Invoices\RaiseInvoiceForBooking;
use App\Mail\EventConfirmationStudent;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * The course confirmation for one seat, with its invoice ([[10-mail]]).
 *
 * **The PDF is made here, then attached**: rendering is the business of the
 * document ([[InvoiceDocument]]), not of the mail, which is how legacy got
 * invoices created twice or not at all. On the queue, because dompdf takes a
 * second a page and confirming a course sends one per seat.
 */
class SendCourseConfirmation implements ShouldQueue
{
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;

	public function __construct(public readonly Booking $booking)
	{
		$this->afterCommit();
	}

	/**
	 * The invoice is asked for, not assumed: confirming a course raises them
	 * in a listener of its own, and listeners are not run in a promised order.
	 * [[RaiseInvoiceForBooking]] returns the one already raised, or raises it
	 * (it is safe to call twice), so this job cannot outrun it. A free course
	 * has none.
	 */
	public function handle(RaiseInvoiceForBooking $raise, InvoiceDocument $pdf): void
	{
		$invoice = $this->booking->invoice() ?? $raise->execute($this->booking);

		Mail::to($this->booking->user)->send(new EventConfirmationStudent($this->booking, $invoice ? $pdf->execute($invoice) : null));
	}
}
