<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserAddress> */
class UserAddressFactory extends Factory
{
	protected $model = UserAddress::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'user_id' => User::factory(),
			'first_name' => fake()->firstName(),
			'last_name' => fake()->lastName(),
			'street' => fake()->streetName(),
			'street_no' => (string) fake()->buildingNumber(),
			'zip' => (string) fake()->numberBetween(1000, 9999),
			'city' => fake()->city(),
			'country_code' => Country::query()->value('code')
				?? Country::factory()->swiss()->create()->code,
		];
	}
}
