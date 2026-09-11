<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

/**
 * Updates a course date. Replacing the date list rewrites `events.date` too,
 * keeping the event's sort date and its displayed dates in step.
 */
class UpdateEvent
{
	/**
	 * @param  array<string, mixed>  $attributes
	 * @param  null|array<int, array{date: string, time_start?: ?string, time_end?: ?string}>  $dates
	 * @param  null|array<int, int>  $expertIds
	 */
	public function execute(Event $event, array $attributes, ?array $dates = null, ?array $expertIds = null): Event
	{
		return DB::transaction(function () use ($event, $attributes, $dates, $expertIds): Event {
			if ($dates !== null && $dates !== []) {
				$sorted = collect($dates)->sortBy('date')->values();
				$attributes['date'] = $sorted->first()['date'];

				$event->dates()->delete();
				$event->dates()->createMany($sorted->all());
			}

			$event->update($attributes);

			if ($expertIds !== null) {
				$event->experts()->sync($expertIds);
			}

			return $event->refresh();
		});
	}
}
