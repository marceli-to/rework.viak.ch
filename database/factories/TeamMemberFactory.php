<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeamMember> */
class TeamMemberFactory extends Factory
{
	protected $model = TeamMember::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'name' => fake()->name(),
			'role' => ['de' => fake()->randomElement(['Kursadministration', 'Geschäftsleitung', 'Marketing'])],
			'publish' => true,
			'order' => 0,
		];
	}
}
