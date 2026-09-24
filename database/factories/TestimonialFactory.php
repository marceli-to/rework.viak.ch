<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Testimonial> */
class TestimonialFactory extends Factory
{
	protected $model = Testimonial::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'quote' => ['de' => fake()->sentence(14)],
			'name' => fake()->name(),
			'context' => ['de' => fake()->company().', '.fake()->city()],
			'featured' => false,
			'publish' => true,
			'order' => 0,
		];
	}
}
