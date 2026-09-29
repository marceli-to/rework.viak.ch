<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * An account an admin creates, from the expert or the student form
 * ([[07-dashboard]], step 6).
 *
 * **No invite yet.** The person should get *Dein VIAK-Zugang*, a signed link
 * to set their own password (#26 for students; legacy's `ExpertCreated` for
 * experts). That mail is chunk 10, and it belongs here, once. Until then the
 * account exists with a password nobody knows and cannot be signed in to.
 *
 * The address counts as verified, as legacy has it: the admin typed it, and
 * the invite will be what proves it.
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

			return $user;
		});
	}
}
