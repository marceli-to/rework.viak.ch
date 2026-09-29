<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Models\User;

/**
 * A new address is unverified until the person confirms it, whoever changed
 * it (Marcel, 2026-09-29): the admin on the expert or student form too, not
 * only the person in their portal ([[UpdateProfile]]). A mistyped address
 * then never becomes a verified one that mail and password resets go to.
 */
class RequireEmailConfirmation
{
	public function execute(User $user): void
	{
		$user->forceFill(['email_verified_at' => null])->save();
		$user->sendEmailVerificationNotification();
	}
}
