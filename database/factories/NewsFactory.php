<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<News> */
class NewsFactory extends Factory
{
	protected $model = News::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'title' => ['de' => fake()->sentence(3)],
			'text' => ['de' => fake()->sentence(10)],
			'link' => 'page:training',
			'publish' => true,
			'order' => 0,
		];
	}
}
