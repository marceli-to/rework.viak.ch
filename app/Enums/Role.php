<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who someone is in the system.
 *
 * Legacy modelled this as a many-to-many `role_user` pivot, but the data never
 * needed it: of 468 users, 465 hold exactly one role, and all three exceptions
 * are Admins who additionally hold Expert and/or Student. So the pivot only
 * ever expressed a hierarchy, and a single column with `atLeast()` expresses
 * the same thing without the join.
 *
 * Migration maps each legacy user to their highest role. Verified lossless
 * against `viak_legacy` — see [[01-schema]].
 */
enum Role: string
{
	case Student = 'student';
	case Expert = 'expert';
	case Admin = 'admin';

	private function rank(): int
	{
		return match ($this) {
			self::Student => 1,
			self::Expert => 2,
			self::Admin => 3,
		};
	}

	/** Admin satisfies Expert, Expert satisfies Student. */
	public function atLeast(self $role): bool
	{
		return $this->rank() >= $role->rank();
	}
}
