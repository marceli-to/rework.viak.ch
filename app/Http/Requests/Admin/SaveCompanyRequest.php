<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\CompanySchema;
use Illuminate\Foundation\Http\FormRequest;

/** *Firma* ([[16-firmenkurse]]), the shape [[CompanyFormResource]] hands out. */
class SaveCompanyRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From [[CompanySchema]], the form's one declaration. */
	public function rules(): array
	{
		return (new CompanySchema)->rules($this->route('company'));
	}

	public function messages(): array
	{
		return (new CompanySchema)->messages();
	}

	public function attributes(): array
	{
		return (new CompanySchema)->attributes();
	}

	/** @return array<string, mixed> */
	public function companyAttributes(): array
	{
		$data = $this->validated();
		$optional = fn (string $key) => ($data[$key] ?? '') === '' ? null : $data[$key];

		return [
			'company' => $data['company'],
			'first_name' => $optional('first_name'),
			'last_name' => $optional('last_name'),
			'street' => $data['street'],
			'street_no' => $optional('street_no'),
			'zip' => $data['zip'],
			'city' => $data['city'],
			'country_code' => $data['country'],
		];
	}
}
