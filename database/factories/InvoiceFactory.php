<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'number' => str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
			'user_id' => User::factory(),
			'status' => InvoiceStatus::Open,
			'date' => today(),
			'due_at' => today()->addDays(10),
			'net' => '0.00',
			'discount' => '0.00',
			'vat' => '0.00',
			'grand_total' => '0.00',
		];
	}

	public function paid(): static
	{
		return $this->state(fn () => [
			'status' => InvoiceStatus::Paid,
			'paid_at' => now(),
		]);
	}

	public function overdue(): static
	{
		return $this->state(fn () => [
			'status' => InvoiceStatus::Overdue,
			'due_at' => today()->subDays(5),
		]);
	}

	public function cancelled(): static
	{
		return $this->state(fn () => [
			'status' => InvoiceStatus::Cancelled,
			'cancelled_at' => now(),
		]);
	}
}
