<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Course;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
	protected $model = Course::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		$title = fake()->unique()->sentence(3);

		return [
			'number' => fake()->unique()->numberBetween(1, 100000),
			'title' => ['de' => $title, 'en' => $title],
			'slug' => Slug::forTitles(['de' => $title, 'en' => $title]),
			'subtitle' => ['de' => fake()->sentence(4)],
			'fee' => fake()->randomFloat(2, 100, 2000),
			'online' => false,
			'publish' => true,
			'order' => 0,
		];
	}

	public function unpublished(): static
	{
		return $this->state(['publish' => false]);
	}
}
