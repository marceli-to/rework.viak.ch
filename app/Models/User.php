<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'role', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
	use HasApiTokens;

	/** @use HasFactory<UserFactory> */
	use HasFactory;

	use HasUuid;
	use Notifiable;

	/** @return array<string, string> */
	protected function casts(): array
	{
		return [
			'email_verified_at' => 'datetime',
			'password' => 'hashed',
			'role' => Role::class,
		];
	}

	/** Course dates this user teaches. Only experts and admins have any. */
	public function eventsAsExpert(): BelongsToMany
	{
		return $this->belongsToMany(Event::class, 'event_expert');
	}

	public function isAdmin(): bool
	{
		return $this->role === Role::Admin;
	}

	public function isAtLeast(Role $role): bool
	{
		return $this->role->atLeast($role);
	}
}
