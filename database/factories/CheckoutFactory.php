<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Checkout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Checkout> */
class CheckoutFactory extends Factory
{
	protected $model = Checkout::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'user_id' => User::factory(),
			'discount_amount' => '0.00',
			'net' => '0.00',
			'completed_at' => now(),
		];
	}

	public function discounting(string $amount): static
	{
		return $this->state(fn () => ['discount_amount' => $amount]);
	}
}
