<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\MessagePosted;
use App\Mail\EventMessageCustomer;
use App\Mail\EventMessageExpert;
use Illuminate\Support\Facades\Mail;

/**
 * A course message goes to everyone [[PostMessage]] recorded as a recipient
 * ([[10-mail]]): each student holding a seat, the author when they asked
 * for a copy, and the Kursleitung when the dashboard ticked theirs. One mail each, as legacy. Its files are links in the mail, to the
 * gated download, not attachments.
 */
class SendMessageMails
{
	public function handle(MessagePosted $posted): void
	{
		$message = $posted->message->loadMissing(['author', 'media', 'recipients', 'event.course', 'event.dates', 'event.location', 'event.experts']);

		foreach ($message->recipients as $recipient) {
			Mail::to($recipient)->send($recipient->is($message->author) || $message->event->experts->contains($recipient)
				? new EventMessageExpert($message)
				: new EventMessageCustomer($message));
		}
	}
}
