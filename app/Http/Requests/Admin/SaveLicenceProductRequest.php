<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\LicenceProductSchema;
use App\Models\LicenceProduct;
use App\Models\Manufacturer;
use App\Models\Software;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The dashboard's licence form ([[05-licences]]), in the shape
 * [[LicenceProductFormResource]] hands out. Copy is written as `['de' => …]`.
 */
class SaveLicenceProductRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From [[LicenceProductSchema]], the form's one declaration. */
	public function rules(): array
	{
		return (new LicenceProductSchema)->rules($this->product());
	}

	public function messages(): array
	{
		return (new LicenceProductSchema)->messages();
	}

	public function attributes(): array
	{
		return (new LicenceProductSchema)->attributes();
	}

	/** @return array<string, mixed> */
	public function productAttributes(): array
	{
		$data = $this->validated();
		$hosts = collect(explode(',', (string) ($data['hosts'] ?? '')))->map(fn (string $host) => trim($host))->filter()->values()->all();

		return [
			'software_id' => Software::query()->where('uuid', $data['software'])->value('id'),
			'manufacturer_id' => Manufacturer::query()->where('uuid', $data['manufacturer'])->value('id'),
			'title' => ['de' => $data['title']],
			'description' => ['de' => blank($data['description'] ?? null) ? null : $data['description']],
			'hosts' => $hosts ?: null,
			'three_years_on_request' => (bool) ($data['three_years_on_request'] ?? false),
			'publish' => (bool) ($data['publish'] ?? false),
		];
	}

	/**
	 * The variants in the order they stand, each with its uuid if it is saved
	 * already.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function variants(): array
	{
		return collect($this->validated()['variants'])->values()->map(fn (array $row, int $position) => [
			'uuid' => $row['uuid'] ?? null,
			'title' => ['de' => $row['title']],
			'sku' => trim($row['sku']),
			'price' => number_format((float) $row['price'], 2, '.', ''),
			'licence_type' => $row['licence_type'] ?? null,
			'access' => $row['access'] ?? null,
			'platforms' => array_values($row['platforms'] ?? []),
			'note' => blank($row['note'] ?? null) ? null : ['de' => $row['note']],
			'min_quantity' => blank($row['min_quantity'] ?? null) ? null : (int) $row['min_quantity'],
			'listed' => (bool) ($row['listed'] ?? false),
			'order' => $position + 1,
		])->all();
	}

	private function product(): ?LicenceProduct
	{
		$product = $this->route('product');

		return $product instanceof LicenceProduct ? $product : null;
	}
}
