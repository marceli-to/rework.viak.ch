<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\Media;
use App\Models\Message;
use App\Models\User;

/**
 * Who may download an uploaded file ([[08-accounts]]).
 *
 * Only the **attachments** go through here — the course materials on an event
 * and the files on a message. Images are a different thing entirely: they are
 * published content, they are served by `/img/{path}` at whatever size the page
 * asked for, and putting a policy in front of them would gate the course
 * photographs on the public site.
 *
 * So the rule is stated as a whitelist rather than a blacklist: an attachment is
 * downloadable when its owner is an Event or a Message the caller belongs to,
 * and **nothing else here is downloadable at all**. A Media row on a Course or a
 * User falls through to `false`, which is right — there is no non-image upload
 * on either, and if one ever appears it should have to be let through
 * deliberately.
 *
 * Legacy had no check of any kind: `FileController` served every row to any
 * authenticated user, which is the same shape as findings 4 and 5.
 */
class MediaPolicy
{
	public function download(User $user, Media $media): bool
	{
		$owner = $media->mediable;

		if ($owner instanceof Event) {
			return $this->belongsToEvent($user, $owner);
		}

		if ($owner instanceof Message) {
			// The thread's own rule, which is the event's rule. A message
			// attachment is not more private than the message it hangs off.
			return $user->can('view', $owner);
		}

		return false;
	}

	/**
	 * Uploading course materials to an event — the expert portal's
	 * *Dokumente hochladen* ([[08-accounts]]).
	 *
	 * The same people who may post a note to the course, and deliberately so:
	 * `EventFileController::store` and `EventMessageController::store` put the
	 * file in the same place and mail the same people, so one of them being
	 * staff-only and the other open to any authenticated user would be an
	 * accident rather than a rule. Legacy gates neither by object.
	 */
	public function createForEvent(User $user, Event $event): bool
	{
		return $user->isAdmin() || $user->eventsAsExpert()->whereKey($event->getKey())->exists();
	}

	/**
	 * Removing one again, which only the course's own staff may do.
	 *
	 * **A message's attachment is not deletable here**, and in the rework that
	 * falls out of the data rather than being a rule: a `media` row has one
	 * owner, so a file under *Kurs-Dokumente* is the event's and a file under a
	 * message is the message's. Legacy needs an explicit `belongs_to_message`
	 * flag on every row because its `fileables` pivot is many-to-many — and the
	 * flag it renders is **always false in this list**, because its event files
	 * and its message files are disjoint sets (13 and 20 rows, measured). One
	 * more rule that has never matched anything.
	 */
	public function delete(User $user, Media $media): bool
	{
		$owner = $media->mediable;

		return $owner instanceof Event && $this->createForEvent($user, $owner);
	}

	/**
	 * Admins, the experts teaching the course, and the students holding a live
	 * seat on it — the same three [[MessagePolicy]] admits, and deliberately the
	 * same: a student who may read what the expert wrote may open what they
	 * attached.
	 *
	 * A cancelled booking does not keep the materials open.
	 */
	private function belongsToEvent(User $user, Event $event): bool
	{
		if ($user->isAdmin()) {
			return true;
		}

		if ($user->eventsAsExpert()->whereKey($event->getKey())->exists()) {
			return true;
		}

		return $event->bookings()->active()->where('user_id', $user->id)->exists();
	}
}
