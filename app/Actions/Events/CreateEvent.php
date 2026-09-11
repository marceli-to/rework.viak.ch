<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\EventState;
use App\Models\Course;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

/**
 * Creates a course date ([[02-courses-events]]).
 *
 * An event's own `date` is always the first of its EventDates — legacy let the
 * two drift, so an event could sort under one date and display another. Here
 * the dates are the input and `events.date` is derived from them.
 */
class CreateEvent
{
	/**
	 * @param  array<string, mixed>  $attributes
	 * @param  array<int, array{date: string, time_start?: ?string, time_end?: ?string}>  $dates
	 * @param  array<int, int>  $expertIds
	 */
	public function execute(Course $course, array $attributes, array $dates, array $expertIds = []): Event
	{
		return DB::transaction(function () use ($course, $attributes, $dates, $expertIds): Event {
			$sorted = collect($dates)->sortBy('date')->values();

			$event = $course->events()->create([
				...$attributes,
				'state' => EventState::Planned,
				'date' => $sorted->first()['date'],
			]);

			$event->dates()->createMany($sorted->all());

			if ($expertIds !== []) {
				$event->experts()->sync($expertIds);
			}

			return $event;
		});
	}
}
