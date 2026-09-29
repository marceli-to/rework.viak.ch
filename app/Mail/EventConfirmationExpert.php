<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use App\Support\SiteUrl;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** *Bestätigung – …* to each expert of a date that is confirmed ([[10-mail]]). Legacy's own. */
class EventConfirmationExpert extends VIAKMail
{
	public function __construct(
		public readonly Event $event,
		public readonly User $expert,
	) {
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Bestätigung – '.$this->course());
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.event.confirmation', with: [
			'recipient' => 'expert',
			'event' => $this->event,
			'user' => $this->expert,
			'course' => $this->course(),
			'dates' => EventFacts::dates($this->event),
			'experts' => EventFacts::experts($this->event),
			'expertPage' => url(SiteUrl::expertEvent($this->event->uuid, 'de')),
		]);
	}

	private function course(): string
	{
		return $this->event->course->getTranslation('title', 'de');
	}
}
