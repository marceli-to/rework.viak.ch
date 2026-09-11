<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventState;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One scheduled instance of a Course — what a student actually books.
 *
 * Legacy `Event` was 448 LOC with 14 `$appends`, so every query serialised
 * a dozen computed attributes whether or not the caller wanted them. Those
 * are now the concern of EventResource. State handling is in [[EventState]].
 */
class Event extends Model
{
	use HasFactory;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = [
		'date', 'registration_until',
		'min_participants', 'max_participants',
		'state', 'confirmed_at', 'cancelled_at', 'closed_at',
		'rentals_available', 'online', 'free_of_charge', 'publish',
		'fee', 'course_id', 'location_id',
	];

	protected function casts(): array
	{
		return [
			'date' => 'date',
			'registration_until' => 'date',
			'confirmed_at' => 'datetime',
			'cancelled_at' => 'datetime',
			'closed_at' => 'datetime',
			'state' => EventState::class,
			'rentals_available' => 'boolean',
			'online' => 'boolean',
			'free_of_charge' => 'boolean',
			'publish' => 'boolean',
			'fee' => 'decimal:2',
		];
	}

	public function course(): BelongsTo
	{
		return $this->belongsTo(Course::class);
	}

	public function location(): BelongsTo
	{
		return $this->belongsTo(Location::class);
	}

	public function dates(): HasMany
	{
		return $this->hasMany(EventDate::class)->orderBy('date');
	}

	public function experts(): BelongsToMany
	{
		return $this->belongsToMany(User::class, 'event_expert');
	}

	/**
	 * The fee a student pays: the event's own, falling back to the course's.
	 * Free events are free regardless of either.
	 */
	public function fee(): string
	{
		if ($this->free_of_charge) {
			return '0.00';
		}

		return (string) ($this->fee ?? $this->course->fee);
	}

	public function scopePublished(Builder $query): Builder
	{
		return $query->where('publish', true);
	}

	public function scopeActive(Builder $query): Builder
	{
		return $query->where('state', '!=', EventState::Cancelled);
	}

	public function scopeInState(Builder $query, EventState $state): Builder
	{
		return $query->where('state', $state);
	}

	/**
	 * Legacy used `date > today` for upcoming and `date < today` for past,
	 * so an event happening *today* was neither. Today counts as upcoming.
	 */
	public function scopeUpcoming(Builder $query): Builder
	{
		return $query->whereDate('date', '>=', today())->orderBy('date');
	}

	public function scopePast(Builder $query): Builder
	{
		return $query->whereDate('date', '<', today())->orderByDesc('date');
	}
}
