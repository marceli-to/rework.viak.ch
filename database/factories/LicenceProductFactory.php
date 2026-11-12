<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LicenceProduct;
use App\Models\Manufacturer;
use App\Models\Software;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LicenceProduct> */
class LicenceProductFactory extends Factory
{
	protected $model = LicenceProduct::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		$title = fake()->unique()->randomElement(['V-Ray', 'Enscape', 'Corona', 'Bongo 2', 'Lumion Pro', 'KeyShot Studio', 'Veras', 'Twinmotion']);

		return [
			'software_id' => fn () => Software::create(['title' => ['de' => fake()->unique()->word()], 'order' => 0, 'publish' => true])->id,
			'manufacturer_id' => fn () => Manufacturer::create(['title' => ['de' => fake()->unique()->company()], 'order' => 0, 'publish' => true])->id,
			'title' => ['de' => $title],
			'slug' => Slug::forTitles($title),
			'three_years_on_request' => false,
			'publish' => true,
			'order' => 0,
		];
	}
}
