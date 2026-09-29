<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Message;
use App\Support\RichText;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A course message, to one booked student ([[10-mail]]): when it is posted,
 * and again to anyone who books later (legacy's `Message::past`, kept as it
 * is: one mail per message, Marcel 2026-09-24).
 *
 * `$post`, not `$message`: Laravel hands every mail view a `$message` of its
 * own, the outgoing mail. Not `readonly`: [[EventMessageExpert]] extends this,
 * and the queue cannot restore a parent's readonly property from a child.
 */
class EventMessageStudent extends VIAKMail
{
	public function __construct(public Message $post)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: $this->post->subject);
	}

	public function content(): Content
	{
		$event = $this->post->event;

		return new Content(markdown: 'mail.event.message-student', with: [
			'post' => $this->post,
			'event' => $event,
			'body' => RichText::render($this->post->body),
			'course' => $event->course->getTranslation('title', 'de'),
			'dates' => EventFacts::dates($event),
			'place' => EventFacts::place($event),
		]);
	}
}
