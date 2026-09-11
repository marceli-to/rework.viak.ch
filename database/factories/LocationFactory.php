<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
	protected $model = Location::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'description' => ['de' => fake()->company()],
			'address' => ['de' => fake()->address()],
			'publish' => true,
		];
	}
}
