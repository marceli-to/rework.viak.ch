<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\DiscountCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountCode>
 */
class DiscountCodeFactory extends Factory
{
	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'code' => mb_strtoupper(fake()->unique()->bothify('???###')),
			'type' => DiscountType::Fixed,
			'amount' => '50.00',
		];
	}

	public function percent(string $amount = '50.00'): static
	{
		return $this->state(fn () => ['type' => DiscountType::Percent, 'amount' => $amount]);
	}
}
