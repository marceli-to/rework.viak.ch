<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\DiscountType;
use App\Forms\DiscountCodeSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The dashboard's discount-code form ([[07-dashboard]], step 6), the shape
 * [[DiscountCodeFormResource]] hands out.
 */
class SaveDiscountCodeRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From [[DiscountCodeSchema]], the form's one declaration. */
	public function rules(): array
	{
		return (new DiscountCodeSchema)->rules($this->route('discountCode'));
	}

	public function messages(): array
	{
		return (new DiscountCodeSchema)->messages();
	}

	public function attributes(): array
	{
		return (new DiscountCodeSchema)->attributes();
	}

	/** More than all of it is not a discount. */
	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator): void {
			if (! $validator->errors()->has('amount')
				&& $this->input('type') === DiscountType::Percent->value
				&& (float) $this->input('amount') > 100) {
				$validator->errors()->add('amount', 'Höchstens 100 Prozent.');
			}
		});
	}

	/** @return array<string, mixed> */
	public function codeAttributes(): array
	{
		$data = $this->validated();
		$optional = fn (string $key) => ($data[$key] ?? '') === '' ? null : $data[$key];

		return [
			'code' => $data['code'],
			'amount' => $data['amount'],
			'type' => $data['type'],
			'valid_from' => $optional('valid_from'),
			'valid_to' => $optional('valid_to'),
			'usage_limit' => $optional('usage_limit'),
			'remarks' => $optional('remarks'),
		];
	}
}
