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
 * *Teilnahmebestätigung – …* with the certificate, to a student ticked as
 * attended, when the course closes ([[10-mail]]). Legacy's own, which built
 * the PDF while rendering; here [[SendParticipationConfirmation]] makes it first.
 */
class EventClosedStudent extends VIAKMail
{
	use AttachesDocument;

	public function __construct(
		public readonly Booking $booking,
		public readonly UserDocument $certificate,
	) {
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Teilnahmebestätigung – '.$this->course());
	}

	public function content(): Content
	{
		$event = $this->booking->event;

		return new Content(markdown: 'mail.event.attendance', with: [
			'course' => $this->course(),
			'dates' => EventFacts::dates($event),
			'experts' => EventFacts::experts($event),
			'place' => EventFacts::place($event),
			'portal' => url(SiteUrl::customerPortal('de')),
		]);
	}

	/** @return array<int, Attachment> */
	public function attachments(): array
	{
		return $this->attachDocument($this->certificate);
	}

	private function course(): string
	{
		return $this->booking->event->course->getTranslation('title', 'de');
	}
}
