<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Event;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The office, when a course date crosses a threshold ([[10-mail]]): legacy's
 * `ParticipantsMin`, `ParticipantsMax` and `ParticipantsBelowMin`, which shared
 * one view and differed in a word. `$type` is `min`, `max` or `belowMin`.
 */
class ParticipantsChange extends VIAKMail
{
	private const TITLES = [
		'min' => 'Min. Teilnehmerzahl erreicht',
		'max' => 'Max. Teilnehmerzahl erreicht',
		'belowMin' => 'Min. Teilnehmerzahl unterschritten',
	];

	public function __construct(
		public readonly Event $event,
		public readonly string $type,
	) {
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: self::TITLES[$this->type].' – '.$this->course());
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.event.participants-change', with: [
			'title' => self::TITLES[$this->type],
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
