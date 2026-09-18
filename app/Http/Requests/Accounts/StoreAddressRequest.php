<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
	public function authorize(): bool
	{
		$address = $this->route('address');

		return $address === null
			? $this->user() !== null
			: ($this->user()?->can('update', $address) ?? false);
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			'first_name' => ['required', 'string', 'max:255'],
			'last_name' => ['required', 'string', 'max:255'],
			'company' => ['nullable', 'string', 'max:255'],
			'street' => ['required', 'string', 'max:255'],
			'street_no' => ['nullable', 'string', 'max:20'],
			'zip' => ['required', 'string', 'max:20'],
			'city' => ['required', 'string', 'max:255'],
			'country_code' => ['required', 'string', 'size:2', 'exists:countries,code'],
		];
	}
}
