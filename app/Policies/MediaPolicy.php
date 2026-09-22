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
