<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\LicenceVariantSchema;
use App\Models\LicenceVariant;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The dashboard's variant form ([[05-licences]]), in the shape
 * [[LicenceVariantFormResource]] hands out.
 */
class SaveLicenceVariantRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From [[LicenceVariantSchema]], the form's one declaration. */
	public function rules(): array
	{
		$variant = $this->route('variant');

		return (new LicenceVariantSchema)->rules($variant instanceof LicenceVariant ? $variant : null);
	}

	public function messages(): array
	{
		return (new LicenceVariantSchema)->messages();
	}

	public function attributes(): array
	{
		return (new LicenceVariantSchema)->attributes();
	}

	/** @return array<string, mixed> */
	public function variantAttributes(): array
	{
		$data = $this->validated();

		return [
			'title' => ['de' => $data['title']],
			'sku' => trim($data['sku']),
			'price' => number_format((float) $data['price'], 2, '.', ''),
			'licence_type' => $data['licence_type'] ?? null,
			'access' => $data['access'] ?? null,
			'platforms' => array_values($data['platforms'] ?? []),
			'note' => blank($data['note'] ?? null) ? null : ['de' => $data['note']],
			'min_quantity' => blank($data['min_quantity'] ?? null) ? null : (int) $data['min_quantity'],
			'listed' => (bool) ($data['listed'] ?? false),
		];
	}
}
