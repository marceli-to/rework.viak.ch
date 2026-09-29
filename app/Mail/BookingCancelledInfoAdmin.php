<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** *Abmeldung für …* to the office ([[10-mail]]). Legacy's own, which names no student either. */
class BookingCancelledInfoAdmin extends VIAKMail
{
	public function __construct(
		public readonly Event $event,
		public readonly ?User $recipient,
	) {
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Abmeldung für '.$this->course());
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.booking.cancellation-info', with: [
			'greeting' => $this->recipient?->first_name ?? '',
			'course' => $this->course(),
			'dates' => EventFacts::dates($this->event),
			'place' => EventFacts::place($this->event),
		]);
	}

	private function course(): string
	{
		return $this->event->course->getTranslation('title', 'de');
	}
}
