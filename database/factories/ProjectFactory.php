<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
	protected $model = Project::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		$title = fake()->unique()->randomElement(['Räume visualisieren', 'Objekte entwerfen', 'Bilder gestalten', 'Bewegtbild erstellen', 'Mit KI gestalten', 'Teams & Unternehmen']);

		return [
			'title' => ['de' => $title],
			'slug' => Slug::forTitles($title),
			'teaser' => ['de' => 'Enscape, Twinmotion, Lumion, V-Ray'],
			'lead' => ['de' => fake()->sentence()],
			'text' => ['de' => '<p>'.fake()->paragraph().'</p>'],
			'publish' => true,
			'order' => 0,
		];
	}
}
