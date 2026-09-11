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

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
	use HasApiTokens;

	/** @use HasFactory<UserFactory> */
	use HasFactory;

	use HasUuid;
	use Notifiable;

	/** @var \Illuminate\Support\Collection<int, Role>|null */
	private ?\Illuminate\Support\Collection $roleNames = null;

	/** @return array<string, string> */
	protected function casts(): array
	{
		return [
			'email_verified_at' => 'datetime',
			'password' => 'hashed',
		];
	}

	/**
	 * Roles held, as a pivot. Cached per request because authorisation asks
	 * repeatedly and these never change mid-request.
	 *
	 * @return \Illuminate\Support\Collection<int, Role>
	 */
	public function roles(): \Illuminate\Support\Collection
	{
		return $this->roleNames ??= \Illuminate\Support\Facades\DB::table('role_user')
			->where('user_id', $this->getKey())
			->pluck('role')
			->map(fn (string $role) => Role::from($role));
	}

	public function hasRole(Role $role): bool
	{
		return $this->roles()->contains($role);
	}

	/** Course dates this user teaches. Only experts and admins have any. */
	public function eventsAsExpert(): BelongsToMany
	{
		return $this->belongsToMany(Event::class, 'event_expert');
	}

	public function isAdmin(): bool
	{
		return $this->hasRole(Role::Admin);
	}

	/**
	 * Teaches courses. Holding the role is necessary but not sufficient for
	 * appearing on the public Experten page — that also needs the publish and
	 * visible flags, as it does today.
	 */
	public function isExpert(): bool
	{
		return $this->hasRole(Role::Expert);
	}
}
