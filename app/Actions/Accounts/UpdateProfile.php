<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Changes a user's own details ([[08-accounts]]).
 *
 * The replacement for legacy's `StudentController::update`, which is the same
 * code in three places — student, expert and admin — and gets two things wrong
 * in each of them.
 *
 * ## Changing an email address needs verifying again
 *
 * Legacy did this:
 *
 *     if ($request->input('new_email')) {
 *       $user->email = $request->input('new_email');
 *       $user->save();
 *     }
 *
 * `email_verified_at` is untouched, so **the new address silently inherits
 * verified status without ever being verified**. Mail then goes to an address
 * nobody has confirmed, and the account's recovery path points somewhere
 * unproven. Here the change clears verification and the address has to be
 * confirmed like any other.
 *
 * ## Both changes need the current password
 *
 * Neither did in legacy, on any of the three roles. A session left open on a
 * shared machine was enough to take an account over permanently — change the
 * address, then the password, and the original owner has no way back.
 *
 * This is the quieter cousin of the `/expert/finish` hole recorded in `Todo.md`,
 * which needs fixing in the legacy tree rather than waiting for this.
 */
class UpdateProfile
{
	/** @param  array<string, mixed>  $attributes */
	public function execute(
		User $user,
		array $attributes,
		?string $email = null,
		?string $password = null,
		?string $currentPassword = null,
	): User {
		$changingCredentials = $email !== null || $password !== null;

		if ($changingCredentials && ! $this->confirms($user, $currentPassword)) {
			throw new RuntimeException('The current password is required to change an email address or a password.');
		}

		$user->fill($attributes);

		if ($password !== null) {
			$user->password = Hash::make($password);
		}

		if ($email !== null && $email !== $user->email) {
			$user->email = $email;

			// The whole point. A new address is unverified until it is proven,
			// however verified the old one was.
			$user->email_verified_at = null;
			$user->save();
			$user->sendEmailVerificationNotification();

			return $user;
		}

		$user->save();

		return $user;
	}

	private function confirms(User $user, ?string $currentPassword): bool
	{
		return $currentPassword !== null && Hash::check($currentPassword, (string) $user->password);
	}
}
