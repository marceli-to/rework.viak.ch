<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\AttachesDocument;
use App\Models\Booking;
use App\Models\UserDocument;
use App\Support\SiteUrl;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * *Annullationsbestätigung* with the late-cancellation cost ([[10-mail]]).
 * The invoice is attached unless it is already paid, when there is nothing to
 * pay ([[SendCancellationConfirmation]]).
 */
class BookingCancelledWithPenalty extends VIAKMail
{
	use AttachesDocument;

	public function __construct(
		public readonly Booking $booking,
		public readonly string $amount,
		public readonly int $percent,
		public readonly bool $paid,
		public readonly ?UserDocument $invoice,
	) {
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Annullationsbestätigung – '.$this->course());
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.booking.cancellation-with-penalty', with: [
			'booking' => $this->booking,
			'course' => $this->course(),
			'dates' => EventFacts::dates($this->booking->event),
			'cost' => EventFacts::money($this->amount),
			'percent' => $this->percent,
			'paid' => $this->paid,
			'courses' => url(SiteUrl::courses('de')),
		]);
	}

	/** @return array<int, Attachment> */
	public function attachments(): array
	{
		return $this->paid ? [] : $this->attachDocument($this->invoice);
	}

	private function course(): string
	{
		return $this->booking->event->course->getTranslation('title', 'de');
	}
}
