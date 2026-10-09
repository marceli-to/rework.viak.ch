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

		return [
			'software_id' => Software::query()->where('uuid', $data['software'])->value('id'),
			'manufacturer_id' => Manufacturer::query()->where('uuid', $data['manufacturer'])->value('id'),
			'title' => ['de' => $data['title']],
			'description' => ['de' => blank($data['description'] ?? null) ? null : $data['description']],
			'three_years_on_request' => (bool) ($data['three_years_on_request'] ?? false),
			'publish' => (bool) ($data['publish'] ?? false),
		];
	}

	/** @return array<int, int> the ticked hosts' ids */
	public function hostIds(): array
	{
		return Software::query()->whereIn('uuid', $this->validated('hosts', []))->pluck('id')->all();
	}

	private function product(): ?LicenceProduct
	{
		$product = $this->route('product');

		return $product instanceof LicenceProduct ? $product : null;
	}
}
