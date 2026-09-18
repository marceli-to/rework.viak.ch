<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Country> */
class CountryFactory extends Factory
{
	protected $model = Country::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'code' => mb_strtoupper(fake()->unique()->lexify('??')),
			'name' => ['de' => fake()->country()],
		];
	}

	/** Switzerland, which is where all but a handful of students are. */
	public function swiss(): static
	{
		return $this->state(fn () => ['code' => 'CH', 'name' => ['de' => 'Schweiz']]);
	}
}
