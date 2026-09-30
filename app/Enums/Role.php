<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a person can do. **Not a hierarchy** — these are capabilities, and one
 * person legitimately holds several.
 *
 * An earlier draft collapsed the legacy `role_user` pivot into a single column
 * with Admin implying Expert implying Student, on the grounds that 465 of 468
 * users held exactly one role. That was wrong, and visibly so: the two people
 * at the top of the public Experten page (users 501 and 2) are Admin + Expert
 * + Student. "Highest role wins" would have dropped both from the page, and
 * `atLeast(Expert)` would instead have swept in five admins who have no bio
 * and teach nothing.
 *
 * So the pivot stays. See [[02-courses-events]].
 *
 * - Admin:   runs the backoffice.
 * - Expert:  teaches courses, has a public bio, appears on the Experten page
 *            (subject to its own publish/visible flags — holding the role is
 *            necessary but not sufficient).
 *
 * **There is no customer role.** Legacy's third, Student, meant "has an
 * account and books", which every account does; it went on 2026-09-30, and
 * every account is a customer (`12-customers.md`). The port drops legacy's
 * role 3.
 */
enum Role: string
{
	case Expert = 'expert';
	case Admin = 'admin';

	/** The legacy `roles.id` this maps to, for the port. */
	public function legacyId(): int
	{
		return match ($this) {
			self::Admin => 1,
			self::Expert => 2,
		};
	}

	/** Legacy's Student (3), and anything else, is no role here. */
	public static function fromLegacyId(int $id): ?self
	{
		return match ($id) {
			1 => self::Admin,
			2 => self::Expert,
			default => null,
		};
	}
}
