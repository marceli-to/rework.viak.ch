<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Concerns;

use App\Enums\Role;
use App\Models\User;

/**
 * A person as the expert and student forms both hold them — the shape
 * [[SavePersonRequest]] takes back.
 */
trait PersonFields
{
	/** @return array<string, mixed> */
	protected function personFields(User $user): array
	{
		return [
			'uuid' => $user->uuid,
			'gender' => $user->gender?->value ?? '',
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
			'company' => $user->company ?? '',
			'email' => $user->email,
			'phone' => $user->phone ?? '',
			'street' => $user->street ?? '',
			'street_no' => $user->street_no ?? '',
			'zip' => $user->zip ?? '',
			'city' => $user->city ?? '',
			'country' => $user->country_code ?? '',
			'subscribe_newsletter' => $user->subscribe_newsletter,
			'roles' => collect(Role::cases())->filter(fn (Role $role) => $user->hasRole($role))->map->value->values()->all(),
		];
	}
}
