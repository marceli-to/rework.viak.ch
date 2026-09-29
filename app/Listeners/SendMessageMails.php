<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\MessagePosted;
use App\Mail\EventMessageExpert;
use App\Mail\EventMessageStudent;
use Illuminate\Support\Facades\Mail;

/**
 * A course message goes to everyone [[PostMessage]] recorded as a recipient
 * ([[10-mail]]): each student holding a seat, and the author when they asked
 * for a copy. One mail each, as legacy. Its files are links in the mail, to the
 * gated download, not attachments.
 */
class SendMessageMails
{
	public function handle(MessagePosted $posted): void
	{
		$message = $posted->message->loadMissing(['author', 'media', 'recipients', 'event.course', 'event.dates', 'event.location']);

		foreach ($message->recipients as $recipient) {
			Mail::to($recipient)->send($recipient->is($message->author)
				? new EventMessageExpert($message)
				: new EventMessageStudent($message));
		}
	}
}
