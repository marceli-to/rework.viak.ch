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
