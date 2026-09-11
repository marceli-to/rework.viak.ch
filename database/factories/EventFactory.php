<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventState;
use App\Models\Course;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
	protected $model = Event::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'course_id' => Course::factory(),
			'date' => now()->addMonth()->toDateString(),
			'min_participants' => 2,
			'max_participants' => 8,
			'state' => EventState::Planned,
			'publish' => true,
		];
	}

	public function in(EventState $state): static
	{
		return $this->state(fn () => array_filter([
			'state' => $state,
			$state->timestampColumn() ?? 'state' => $state->timestampColumn() ? now() : $state,
		]));
	}

	public function past(): static
	{
		return $this->state(['date' => now()->subMonth()->toDateString()]);
	}
}
