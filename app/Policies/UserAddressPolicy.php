<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserAddress;

/**
 * Invoice addresses belong to the person who entered them ([[08-accounts]]).
 *
 * 107 users hold the 125 addresses; 14 of them have more than one. Legacy did
 * check ownership here — `StudentAddressController` is three of the nine
 * `authorize()` calls in the whole application — so this is a convention being
 * kept rather than a hole being closed.
 */
class UserAddressPolicy
{
	public function view(User $user, UserAddress $address): bool
	{
		return $this->owns($user, $address);
	}

	public function update(User $user, UserAddress $address): bool
	{
		return $this->owns($user, $address);
	}

	public function delete(User $user, UserAddress $address): bool
	{
		return $this->owns($user, $address);
	}

	private function owns(User $user, UserAddress $address): bool
	{
		return $user->id === $address->user_id || $user->isAdmin();
	}
}
