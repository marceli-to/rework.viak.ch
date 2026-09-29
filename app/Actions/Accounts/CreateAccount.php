<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\Role;
use App\Mail\AccountInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * An account an admin creates, from the expert or the student form
 * ([[07-dashboard]], step 6).
 *
 * **The person is invited**: *Dein VIAK-Zugang*, with a signed link to set
 * their own password ([[AccountInvitation]], #26; legacy's `ExpertCreated`).
 * Until they use it, the account has a password nobody knows.
 *
 * The address counts as verified, as legacy has it: the admin typed it, and
 * the invite is what proves it.
 */
class CreateAccount
{
	/**
	 * @param  array<string, mixed>  $attributes
	 * @param  array<int, Role>  $roles
	 */
	public function execute(array $attributes, array $roles): User
	{
		return DB::transaction(function () use ($attributes, $roles): User {
			$user = new User($attributes);
			$user->forceFill([
				'password' => Hash::make(Str::random(40)),
				'email_verified_at' => now(),
			])->save();

			$user->syncRoles($roles);

			// Queued after the commit, like every mail ([[VIAKMail]]).
			Mail::to($user)->send(new AccountInvitation($user));

			return $user;
		});
	}
}
