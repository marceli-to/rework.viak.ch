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
 * *Kursbestätigung – …* to a student, with the invoice ([[10-mail]]). Sent when
 * the course is confirmed, and on a booking into one already confirmed.
 *
 * **The PDF is made before this is sent, not by it** ([[SendCourseConfirmation]]).
 * Legacy's version created the invoice while rendering itself; here the
 * document already exists and the mail only attaches it.
 */
class EventConfirmationStudent extends VIAKMail
{
	use AttachesDocument;

	public function __construct(
		public readonly Booking $booking,
		public readonly ?UserDocument $invoice,
	) {
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Kursbestätigung – '.$this->course());
	}

	public function content(): Content
	{
		$event = $this->booking->event;
		$invoice = $this->invoice?->invoice();

		return new Content(markdown: 'mail.event.confirmation', with: [
			'recipient' => 'student',
			'booking' => $this->booking,
			'event' => $event,
			'user' => $this->booking->user,
			'course' => $this->course(),
			'dates' => EventFacts::dates($event),
			'experts' => EventFacts::experts($event),
			'place' => EventFacts::place($event),
			'fee' => EventFacts::money($this->booking->netFee()),
			'payment' => $invoice ? url(SiteUrl::invoicePayment($invoice->uuid, 'de')) : null,
			'portal' => url(SiteUrl::studentPortal('de')),
		]);
	}

	/** @return array<int, Attachment> */
	public function attachments(): array
	{
		return $this->attachDocument($this->invoice);
	}

	private function course(): string
	{
		return $this->booking->event->course->getTranslation('title', 'de');
	}
}
