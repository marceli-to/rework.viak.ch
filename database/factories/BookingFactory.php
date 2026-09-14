<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'number' => str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
			'event_id' => Event::factory(),
			'user_id' => User::factory(),
			'course_fee' => fake()->randomElement(['499.00', '549.00', '899.00', '1200.00']),
			'discount_amount' => '0.00',
			'has_rental' => false,
			'booked_at' => now(),
		];
	}

	public function cancelled(): static
	{
		return $this->state(fn () => ['cancelled_at' => now()]);
	}

	public function withRental(): static
	{
		return $this->state(fn () => ['has_rental' => true]);
	}
}
