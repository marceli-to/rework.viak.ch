<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Media> */
class MediaFactory extends Factory
{
	protected $model = Media::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'file' => fake()->unique()->slug(2).'.jpg',
			'original_name' => 'photo.jpg',
			'mime_type' => 'image/jpeg',
			'size' => 120_000,
			'width' => 2000,
			'height' => 1500,
			'variant' => 'desktop',
			'sort_order' => 0,
		];
	}

	public function cropped(int $w = 1000, int $h = 500, int $x = 0, int $y = 0): static
	{
		return $this->state(fn () => ['crop' => ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h]]);
	}

	public function mobile(): static
	{
		return $this->state(fn () => ['variant' => 'mobile']);
	}
}
