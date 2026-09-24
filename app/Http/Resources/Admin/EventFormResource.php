<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Requests\Admin\SaveEventRequest;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A course date as the dashboard's form holds it ([[07-dashboard]]) — the
 * shape [[SaveEventRequest]] takes back. Times as `H:i`, the location and
 * experts by uuid.
 *
 * Plus what the form reads and never sends: the course it belongs to (the
 * title says *Veranstaltung für …*), its state, whether it is past, and its
 * bookings, which decide whether it may be deleted.
 *
 * @mixin Event
 */
class EventFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$time = fn (?string $value) => $value === null ? '' : substr($value, 0, 5);

		return [
			'uuid' => $this->uuid,
			'registration_until' => $this->registration_until?->toDateString() ?? '',
			'min_participants' => $this->min_participants,
			'max_participants' => $this->max_participants,
			'rentals_available' => $this->rentals_available,
			'fee' => $this->fee === null ? '' : (string) $this->fee,
			'online' => $this->online,
			'free_of_charge' => $this->free_of_charge,
			'publish' => $this->publish,
			'location' => $this->location?->uuid ?? '',
			'dates' => $this->dates->map(fn ($day) => [
				'date' => $day->date->toDateString(),
				'time_start' => $time($day->time_start),
				'time_end' => $time($day->time_end),
			])->all(),
			'experts' => $this->experts->pluck('uuid')->all(),

			'course' => [
				'uuid' => $this->course->uuid,
				'number' => $this->course->number,
				'title' => $this->course->getTranslation('title', 'de'),
			],
			'state' => $this->state->value,
			'is_past' => $this->date->lt(today()),
			// Cancelled ones too: they carry invoices, as a course's do.
			'bookings' => $this->bookings()->count(),
		];
	}
}
