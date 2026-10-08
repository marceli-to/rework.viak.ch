<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LicenceAccess;
use App\Enums\LicenceType;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LicenceVariant> */
class LicenceVariantFactory extends Factory
{
	protected $model = LicenceVariant::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'licence_product_id' => LicenceProduct::factory(),
			'title' => ['de' => fake()->randomElement(['Solo, named', 'Premium, floating', 'Collection, named'])],
			'sku' => fake()->unique()->bothify('???-####'),
			'price' => fake()->randomElement(['450.00', '680.00', '800.00']),
			'licence_type' => LicenceType::Subscription,
			'access' => LicenceAccess::Named,
			'platforms' => ['windows', 'macos'],
			'listed' => true,
			'order' => 0,
		];
	}

	/** A demo: free, no licence type (#35). */
	public function demo(): static
	{
		return $this->state(['title' => ['de' => 'Demoversion'], 'price' => '0.00', 'licence_type' => null]);
	}

	/** Entered by VIAK only, never on the site (#36). */
	public function hidden(): static
	{
		return $this->state(['listed' => false]);
	}
}
