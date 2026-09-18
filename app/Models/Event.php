<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventState;
use App\Enums\ParticipantThreshold;
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
		'min_participants', 'max_participants', 'participant_threshold',
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
			'participant_threshold' => ParticipantThreshold::class,
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

	/** Notes posted to this course and mailed to everyone on it. */
	public function messages(): HasMany
	{
		return $this->hasMany(Message::class);
	}

	public function bookings(): HasMany
	{
		return $this->hasMany(Booking::class);
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

	/**
	 * Seats taken. Cancelled bookings are not among them — 183 of 710 are
	 * cancelled, so counting them would report every course a quarter fuller
	 * than it is and turn people away from free seats.
	 */
	public function seatsTaken(): int
	{
		return $this->bookings()->active()->count();
	}

	public function isFull(): bool
	{
		return $this->max_participants !== null
			&& $this->seatsTaken() >= $this->max_participants;
	}

	/**
	 * May a seat still be sold?
	 *
	 * Published, not cancelled or closed, and not in the past. Legacy asked none
	 * of this at checkout — `Booking::can()` only looked for a duplicate — so a
	 * basket left open while a course was called off still sold a seat on it.
	 */
	public function isBookable(): bool
	{
		return $this->publish
			&& ! in_array($this->state, [EventState::Cancelled, EventState::Closed], true)
			&& $this->date->endOfDay()->isFuture();
	}

	/**
	 * Has the course reached the headcount it needs to run?
	 *
	 * Compared with `>=`, never `==`. Legacy's `ParticipantsChange::handle()`
	 * fired on equality alone, so two bookings landing in one cycle stepped over
	 * the threshold and the notification was lost for good, with nothing to
	 * catch up ([[00-foundation]]).
	 */
	public function hasMinimumParticipants(): bool
	{
		return $this->min_participants !== null
			&& $this->seatsTaken() >= $this->min_participants;
	}

	/**
	 * Which participant band the event is in **right now**.
	 *
	 * All three comparisons are inclusive inequalities, never `==`. That is the
	 * whole fix: a count that jumps from one below the minimum to one above it
	 * still changes band, where legacy's equality test simply missed
	 * ([[ParticipantThreshold]]).
	 */
	public function currentThreshold(): ParticipantThreshold
	{
		$taken = $this->seatsTaken();

		if ($this->max_participants !== null && $taken >= $this->max_participants) {
			return ParticipantThreshold::Full;
		}

		if ($this->min_participants !== null && $taken >= $this->min_participants) {
			return ParticipantThreshold::Viable;
		}

		return ParticipantThreshold::BelowMinimum;
	}

	/**
	 * The "Kurs-Nummer" a student sees: the course number, padded to two
	 * digits, and the event's date — `07-120326`.
	 *
	 * Derived rather than stored, as it was in legacy. The format is not ours
	 * to improve: 569 invoices and every participation confirmation ever sent
	 * carry a number in exactly this shape, and customers quote it back.
	 */
	public function number(): string
	{
		return str_pad((string) $this->course->number, 2, '0', STR_PAD_LEFT)
			.'-'.$this->date->format('dmy');
	}

	/**
	 * The event's days, written the way an invoice line prints them:
	 * `12.–13.03.2026` for consecutive days in one month, `26.01.2026` for a
	 * single day, and a full pair of dates when a course straddles a month.
	 *
	 * Lives on the model rather than in a view because it is frozen into
	 * `invoice_items.description` at issue — the invoice has to keep saying
	 * what it said, so this runs once, not at render time.
	 */
	public function dateRange(): string
	{
		$days = $this->dates->pluck('date')->filter()->sort()->values();

		if ($days->isEmpty()) {
			return $this->date->format('d.m.Y');
		}

		$first = $days->first();
		$last = $days->last();

		if ($first->isSameDay($last)) {
			return $first->format('d.m.Y');
		}

		return $first->isSameMonth($last)
			? $first->format('d.').'–'.$last->format('d.m.Y')
			: $first->format('d.m.').'–'.$last->format('d.m.Y');
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
