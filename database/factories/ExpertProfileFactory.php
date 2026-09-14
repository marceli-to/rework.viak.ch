<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExpertProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpertProfile>
 */
class ExpertProfileFactory extends Factory
{
	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'user_id' => User::factory()->expert(),
			'title' => fake()->sentence(3),
			'description' => '<p>' . fake()->paragraph() . '</p>',
			'order' => fake()->numberBetween(1, 20),
			'publish' => true,
			'visible' => true,
		];
	}

	public function hidden(): static
	{
		return $this->state(fn () => ['visible' => false]);
	}
}
