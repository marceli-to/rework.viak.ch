<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Event;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** *Reminder – …* to the office: confirm or cancel this date ([[10-mail]]). Legacy's own. */
class EventCancelOrConfirmReminder extends VIAKMail
{
	public function __construct(public readonly Event $event)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Reminder – '.$this->course());
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.event.cancel-or-confirm-reminder', with: [
			'course' => $this->course(),
			'dates' => EventFacts::dates($this->event),
			'experts' => EventFacts::experts($this->event),
			'edit' => url('/dashboard/kursdatum/'.$this->event->uuid.'/bearbeiten'),
		]);
	}

	private function course(): string
	{
		return $this->event->course->getTranslation('title', 'de');
	}
}
