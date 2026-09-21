<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseVideo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CourseVideo> */
class CourseVideoFactory extends Factory
{
	protected $model = CourseVideo::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'course_id' => Course::factory(),
			'title' => ['de' => fake()->sentence(4)],
			'code' => '<iframe src="https://www.youtube.com/embed/'.fake()->lexify('???????????').'"></iframe>',
			'order' => 0,
			'publish' => true,
		];
	}

	public function unpublished(): static
	{
		return $this->state(['publish' => false]);
	}
}
