<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceItemType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'invoice_id' => Invoice::factory(),
			'type' => InvoiceItemType::Course,
			'description' => 'Blender Modeling, 12.–13.03.2026',
			'reference' => '07-120326',
			'position' => 1,
			'net' => '499.00',
			'discount' => '0.00',
			'vat_rate' => '0.00',
			'vat' => '0.00',
			'total' => '499.00',
		];
	}

	/** A laptop rental line: the one thing VIAK has ever charged VAT on. */
	public function rental(): static
	{
		return $this->state(fn () => [
			'type' => InvoiceItemType::Rental,
			'description' => 'Laptopmiete',
			'net' => '80.00',
			'vat_rate' => '8.10',
			'vat' => '6.48',
			'total' => '86.48',
		]);
	}
}
