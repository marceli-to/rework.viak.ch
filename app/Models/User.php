<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use App\Enums\OperatingSystem;
use App\Enums\Role;
use App\Models\Concerns\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

/**
 * Implements `MustVerifyEmail` deliberately ([[08-accounts]]).
 *
 * Legacy carried the column and the trait but never the contract, so nothing
 * enforced it — and `StudentController::update` changed an address without
 * clearing `email_verified_at`, meaning a brand-new address inherited verified
 * status without ever being proven. 16 of the 578 users are unverified today.
 */
#[Fillable([
	'first_name', 'last_name', 'company', 'email', 'password',
	'street', 'street_no', 'zip', 'city', 'country_code', 'phone',
	'gender', 'operating_systems', 'subscribe_newsletter',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
	use HasApiTokens;

	/** @use HasFactory<UserFactory> */
	use HasFactory;

	use HasUuid;
	use Notifiable;
	use SoftDeletes;

	/** @var Collection<int, Role>|null */
	private ?Collection $roleNames = null;

	/** @return array<string, string> */
	protected function casts(): array
	{
		return [
			'email_verified_at' => 'datetime',
			'password' => 'hashed',
			'gender' => Gender::class,
			'operating_systems' => AsEnumCollection::class.':'.OperatingSystem::class,
			'subscribe_newsletter' => 'boolean',
		];
	}

	/**
	 * Roles held, as a pivot. Cached per request because authorisation asks
	 * repeatedly and these never change mid-request.
	 *
	 * @return Collection<int, Role>
	 */
	public function roles(): Collection
	{
		return $this->roleNames ??= DB::table('role_user')
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

	/** Generated PDFs — invoices and participation confirmations. */
	public function documents(): HasMany
	{
		return $this->hasMany(UserDocument::class);
	}

	public function checkouts(): HasMany
	{
		return $this->hasMany(Checkout::class);
	}

	/**
	 * Saved courses. 17 rows in three years across the whole site, which sets
	 * the budget rather than the question: a pivot, two methods and a star on
	 * a course card — no facade, no Action layer, no dashboard screen
	 * ([[06-bookings]]).
	 */
	public function bookmarks(): BelongsToMany
	{
		return $this->belongsToMany(Event::class, 'bookmarks')->withTimestamps();
	}

	public function hasBookmarked(Event $event): bool
	{
		return $this->bookmarks()->whereKey($event->getKey())->exists();
	}

	/**
	 * Saving an event you have already booked is noise, so booking clears it.
	 * Legacy did the same thing from inside `Booking::create()`; this keeps the
	 * behaviour and gives it a name.
	 */
	public function forgetBookmark(Event $event): void
	{
		$this->bookmarks()->detach($event->getKey());
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

	/**
	 * Books courses. 555 of 578 users hold this and nothing else.
	 *
	 * Note that Admin does *not* imply Student. Chunk 02 settled that roles are
	 * capabilities rather than a rank — three people hold all three — so an admin
	 * who wants to book a course for themselves holds the Student role too.
	 */
	public function isStudent(): bool
	{
		return $this->hasRole(Role::Student);
	}
}
