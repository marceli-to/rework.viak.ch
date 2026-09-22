<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * Admins manage course dates. Experts may view the ones they teach — including
 * the participant list — but never change state: confirming or cancelling an
 * event sends mail to students and can trigger penalty invoices, so it stays
 * an administrative act.
 */
class EventPolicy
{
	public function viewAny(?User $user): bool
	{
		return true;
	}

	public function view(?User $user, Event $event): bool
	{
		if ($event->publish) {
			return true;
		}

		if (! $user instanceof User) {
			return false;
		}

		return $user->isAdmin() || $this->teaches($user, $event);
	}

	/**
	 * The participant list — names, towns, phone numbers and email addresses
	 * ([[08-accounts]], finding 5).
	 *
	 * **The check legacy does not make.** `GET /pdf/teilnehmer-liste/{event}` is
	 * gated by `role:admin,expert` and nothing else, so any of the 18 accounts
	 * holding the Expert role can download the contact details of every student
	 * on every course in the archive. It is an omission on one route rather than
	 * a missing idea — `EventController::findExpertEvent` next door does call
	 * `authorize('containsEvent', $event)`.
	 *
	 * So the rule is the neighbour's, stated once and asked by every caller: the
	 * expert screen that draws the list, and whatever serves it as a PDF when
	 * there is a PDF to serve. Admins see every course; an expert sees the ones
	 * they teach.
	 */
	public function viewParticipants(User $user, Event $event): bool
	{
		return $user->isAdmin() || $this->teaches($user, $event);
	}

	public function create(User $user): bool
	{
		return $user->isAdmin();
	}

	public function update(User $user, Event $event): bool
	{
		return $user->isAdmin();
	}

	public function setState(User $user, Event $event): bool
	{
		return $user->isAdmin();
	}

	public function delete(User $user, Event $event): bool
	{
		return $user->isAdmin();
	}

	private function teaches(User $user, Event $event): bool
	{
		return $event->experts()->whereKey($user->getKey())->exists();
	}
}
