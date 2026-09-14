<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use App\Enums\OperatingSystem;
use App\Enums\Role;
use App\Models\Concerns\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
	'first_name', 'last_name', 'company', 'email', 'password',
	'street', 'street_no', 'zip', 'city', 'country_code', 'phone',
	'gender', 'operating_systems', 'subscribe_newsletter',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
	use HasApiTokens;

	/** @use HasFactory<UserFactory> */
	use HasFactory;

	use HasUuid;
	use Notifiable;
	use SoftDeletes;

	/** @var \Illuminate\Support\Collection<int, Role>|null */
	private ?\Illuminate\Support\Collection $roleNames = null;

	/** @return array<string, string> */
	protected function casts(): array
	{
		return [
			'email_verified_at' => 'datetime',
			'password' => 'hashed',
			'gender' => Gender::class,
			'operating_systems' => AsEnumCollection::class . ':' . OperatingSystem::class,
			'subscribe_newsletter' => 'boolean',
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

	/**
	 * Display name. Legacy stored the surname in a column called `name`,
	 * which made `$user->name` mean one thing on User and another on every
	 * other model. The columns are `first_name` and `last_name` now; this
	 * composes them for the one job the old column actually did.
	 */
	protected function name(): Attribute
	{
		return Attribute::get(fn (): string => trim("{$this->first_name} {$this->last_name}"));
	}

	public function country(): BelongsTo
	{
		return $this->belongsTo(Country::class, 'country_code', 'code');
	}

	/** Alternate invoice addresses — an employer paying, usually. */
	public function addresses(): HasMany
	{
		return $this->hasMany(UserAddress::class);
	}

	public function bookings(): HasMany
	{
		return $this->hasMany(Booking::class);
	}

	public function expertProfile(): HasOne
	{
		return $this->hasOne(ExpertProfile::class);
	}

	/** Course dates this user teaches. Only experts and admins have any. */
	public function eventsAsExpert(): BelongsToMany
	{
		return $this->belongsToMany(Event::class, 'event_expert');
	}

	/**
	 * The Experten page, exactly as the legacy `ExpertController` builds it:
	 * the role, both flags, ordered by hand. Holding the role is necessary but
	 * not sufficient — 17 people have a bio and 10 are listed.
	 */
	public function scopePubliclyListedExperts(Builder $query): void
	{
		$query
			->join('expert_profiles', 'expert_profiles.user_id', '=', 'users.id')
			->whereIn('users.id', fn ($q) => $q->select('user_id')->from('role_user')->where('role', Role::Expert->value))
			->where('expert_profiles.publish', true)
			->where('expert_profiles.visible', true)
			->orderBy('expert_profiles.order')
			->select('users.*');
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
