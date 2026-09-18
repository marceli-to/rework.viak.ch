<?php

declare(strict_types=1);

namespace App\Actions\Messages;

use App\Models\Event;
use App\Models\Media;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Posts a note to a course and mails everyone on it ([[08-accounts]]).
 *
 * ## What changed from legacy
 *
 * **The recipients are recorded for every send.** Legacy wrote `message_user`
 * rows in this path, but `Booking::create()` also mailed a new student any
 * messages already posted to their course — without writing a row — so the
 * record under-reported who had actually been told what.
 *
 * **The mail goes on the queue, not into a hand-rolled table.** Legacy inserted
 * into its own `jobs` table, which `RunInvoiceBatchProcess` drained two rows a
 * minute with no retries: one confirmed twelve-seat course backed the mailer up
 * for half an hour, and a transient SMTP failure lost that mail permanently
 * ([[00-foundation]]).
 *
 * **A student cannot reach this.** `POST /api/event/message` was gated by
 * `role:admin,expert,student` and nothing else, and `MessageStoreRequest`
 * authorised everything — so any authenticated student could post to any event
 * and mail every participant. It has apparently never been used: none of the 251
 * messages comes from a student-only account. It was still open.
 */
class PostMessage
{
	/**
	 * @param  array<int, Media>  $attachments
	 */
	public function execute(
		Event $event,
		User $author,
		string $subject,
		string $body,
		array $attachments = [],
		bool $copyToAuthor = false,
	): Message {
		return DB::transaction(function () use ($event, $author, $subject, $body, $attachments, $copyToAuthor): Message {
			$message = Message::create([
				'event_id' => $event->id,
				'user_id' => $author->id,
				'subject' => $subject,
				'body' => $body,
			]);

			foreach ($attachments as $media) {
				$media->mediable()->associate($message)->save();
			}

			// Everyone holding a seat *now*. Cancelled bookings are excluded —
			// someone who dropped out has no interest in the room number — and
			// the list is frozen onto the pivot rather than recomputed later.
			$recipients = $event->bookings()->active()->pluck('user_id')->unique();

			if ($copyToAuthor) {
				$recipients = $recipients->push($author->id)->unique();
			}

			$message->recipients()->attach($recipients->all(), ['created_at' => now()]);

			return $message->load('recipients');
		});
	}
}
