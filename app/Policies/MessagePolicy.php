<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\Message;
use App\Models\User;

/**
 * Who may read and write a course's notes ([[08-accounts]]).
 *
 * The authorization legacy did not have. `GET /api/event/messages/{event}` and
 * `POST /api/event/message` were gated by `role:admin,expert,student` with no
 * policy call and no `authorize()` in the FormRequest, so **any authenticated
 * student could read the thread of a course they had never booked, and post a
 * message to one — which mailed every participant.**
 *
 * Writing is staff-only, which is what actually happens: every one of the 251
 * messages was written by someone holding Admin or Expert, and not one came from
 * a student-only account.
 */
class MessagePolicy
{
	public function view(User $user, Message $message): bool
	{
		return $this->belongsToEvent($user, $message->event);
	}

	/** Reading the thread on a course. */
	public function viewForEvent(User $user, Event $event): bool
	{
		return $this->belongsToEvent($user, $event);
	}

	/** Writing to it: the experts who teach it, and admins. */
	public function create(User $user, Event $event): bool
	{
		return $user->isAdmin() || $user->eventsAsExpert()->whereKey($event->getKey())->exists();
	}

	public function delete(User $user, Message $message): bool
	{
		return $user->isAdmin() || $user->id === $message->user_id;
	}

	/**
	 * Admins, the experts teaching the course, and the students holding a live
	 * seat on it. A cancelled booking does not keep the thread open.
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
