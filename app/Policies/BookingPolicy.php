<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * A booking belongs to the student who made it ([[06-bookings]]).
 *
 * Object-level ownership, checked here rather than left to route middleware.
 * That is worth being explicit about: across the whole legacy application there
 * are **four** policies and **nine** `$this->authorize()` calls, and all 28 of
 * its FormRequests return `authorize() => true` — so a route's `role:student`
 * was usually the only thing standing between a student and somebody else's data
 * ([[08-accounts]]).
 */
class BookingPolicy
{
	public function view(User $user, Booking $booking): bool
	{
		return $this->owns($user, $booking) || $user->isAdmin();
	}

	public function update(User $user, Booking $booking): bool
	{
		return $this->owns($user, $booking) || $user->isAdmin();
	}

	/**
	 * Admins may cancel on a student's behalf — a phone call, usually — and that
	 * is recorded as `Administrator` rather than passed off as the student's own
	 * doing. Legacy allowed the same thing through the same route and could not
	 * tell the two apart afterwards.
	 */
	public function cancel(User $user, Booking $booking): bool
	{
		return $this->owns($user, $booking) || $user->isAdmin();
	}

	public function create(User $user): bool
	{
		return $user->isStudent() || $user->isAdmin();
	}

	private function owns(User $user, Booking $booking): bool
	{
		return $user->id === $booking->user_id;
	}
}
