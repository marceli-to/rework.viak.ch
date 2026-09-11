<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

/**
 * Courses are editorial content: admins write, everyone reads what is
 * published. Experts see unpublished courses they teach on, so they can
 * prepare before a course goes live.
 */
class CoursePolicy
{
	public function viewAny(?User $user): bool
	{
		return true;
	}

	public function view(?User $user, Course $course): bool
	{
		if ($course->publish) {
			return true;
		}

		if (! $user instanceof User) {
			return false;
		}

		return $user->isAdmin() || $this->teaches($user, $course);
	}

	public function create(User $user): bool
	{
		return $user->isAdmin();
	}

	public function update(User $user, Course $course): bool
	{
		return $user->isAdmin();
	}

	public function delete(User $user, Course $course): bool
	{
		return $user->isAdmin();
	}

	private function teaches(User $user, Course $course): bool
	{
		return $course->events()
			->whereHas('experts', fn ($query) => $query->whereKey($user->getKey()))
			->exists();
	}
}
