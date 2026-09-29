<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Documents\RenderInvoice;
use App\Enums\DocumentType;
use App\Mail\EventConfirmationStudent;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\UserDocument;
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
 * document ([[RenderInvoice]]), not of the mail, which is how legacy got
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

	public function handle(RenderInvoice $render): void
	{
		$invoice = $this->booking->invoice();
		$document = $invoice ? ($this->existing($invoice) ?? $render->execute($invoice)) : null;

		Mail::to($this->booking->user)->send(new EventConfirmationStudent($this->booking, $document));
	}

	/** The PDF, if one was made already: a confirmation sent twice attaches the same file. */
	private function existing(Invoice $invoice): ?UserDocument
	{
		return UserDocument::query()
			->where('documentable_type', $invoice->getMorphClass())
			->where('documentable_id', $invoice->getKey())
			->where('type', DocumentType::Invoice)
			->first();
	}
}
