<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Legacy appended 14 computed attributes to every Event, so a list query
 * serialised all of them whether the caller wanted them or not. Here the
 * shape is explicit and relation-dependent fields are guarded with
 * `whenLoaded`, so an index endpoint stays an index endpoint.
 *
 * @mixin Event
 */
class EventResource extends JsonResource
{
	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'date' => $this->date?->toDateString(),
			'registration_until' => $this->registration_until?->toDateString(),

			'state' => $this->state->value,
			'accepts_bookings' => $this->state->acceptsBookings(),
			'confirmed_at' => $this->confirmed_at?->toIso8601String(),
			'cancelled_at' => $this->cancelled_at?->toIso8601String(),
			'closed_at' => $this->closed_at?->toIso8601String(),

			'is_past' => $this->date?->isBefore(today()) ?? false,
			'min_participants' => $this->min_participants,
			'max_participants' => $this->max_participants,

			'fee' => $this->fee(),
			'free_of_charge' => $this->free_of_charge,
			'rentals_available' => $this->rentals_available,
			'online' => $this->online,
			'publish' => $this->publish,

			'dates' => $this->whenLoaded('dates', fn () => $this->dates->map(fn ($date) => [
				'date' => $date->date->toDateString(),
				'time_start' => $date->time_start,
				'time_end' => $date->time_end,
			])),

			'course' => new CourseSummaryResource($this->whenLoaded('course')),
			'location' => $this->whenLoaded('location', fn () => [
				'uuid' => $this->location->uuid,
				'description' => $this->location->getTranslations('description'),
				'address' => $this->location->getTranslations('address'),
			]),
			'experts' => $this->whenLoaded('experts', fn () => $this->experts->map(fn ($expert) => [
				'uuid' => $expert->uuid,
				'name' => $expert->name,
			])),
		];
	}
}
