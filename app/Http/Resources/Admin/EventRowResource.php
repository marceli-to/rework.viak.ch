<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One course date as the dashboard lists it ([[07-dashboard]]) — what legacy's
 * row shows: its days and hours, where, who teaches it, its state, and how full
 * it is, laptops included.
 *
 * Times go out as `09.00`, the site's own spelling, so the screen does not
 * reformat a `TIME` column.
 *
 * @mixin Event
 */
class EventRowResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$time = fn (?string $value) => $value === null ? null : str_replace(':', '.', substr($value, 0, 5));

		return [
			'uuid' => $this->uuid,
			'date' => $this->date?->toDateString(),
			'dates' => $this->dates->map(fn ($date) => [
				'date' => $date->date->toDateString(),
				'time_start' => $time($date->time_start),
				'time_end' => $time($date->time_end),
			])->all(),

			'online' => $this->online,
			'location' => $this->location?->getTranslation('description', 'de'),
			'map' => $this->location?->map,
			'experts' => $this->experts->map(fn ($expert) => trim("{$expert->first_name} {$expert->last_name}"))->all(),

			'state' => $this->state->value,
			'publish' => $this->publish,

			'bookings' => $this->bookings_count,
			'max_participants' => $this->max_participants,
			// Legacy's *0 / 2 Mietcomputer*: rented, and how many the room has.
			'rentals' => $this->rentals_taken_count,
			'rentals_available' => $this->rentals_available,
		];
	}
}
