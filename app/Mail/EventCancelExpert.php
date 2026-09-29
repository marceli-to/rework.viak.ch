<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Event;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** *Kursabsage – …* to each expert of a date called off ([[10-mail]]). Legacy's own. */
class EventCancelExpert extends VIAKMail
{
	public function __construct(public readonly Event $event)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Kursabsage – '.$this->course());
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.event.cancel', with: [
			'recipient' => 'expert',
			'course' => $this->course(),
			'dates' => EventFacts::dates($this->event),
			'experts' => EventFacts::experts($this->event),
		]);
	}

	private function course(): string
	{
		return $this->event->course->getTranslation('title', 'de');
	}
}
