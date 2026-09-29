<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\InvoiceSchema;
use Illuminate\Foundation\Http\FormRequest;

/**
 * *Rechnung bearbeiten* ([[07-dashboard]], step 7): the invoice address, in
 * the shape [[InvoiceFormResource]] hands out.
 */
class SaveInvoiceAddressRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From [[InvoiceSchema]], the form's one declaration. */
	public function rules(): array
	{
		return (new InvoiceSchema)->rules($this->route('invoice'));
	}

	public function messages(): array
	{
		return (new InvoiceSchema)->messages();
	}

	public function attributes(): array
	{
		return (new InvoiceSchema)->attributes();
	}

	/**
	 * The snapshot a checkout freezes ([[UserAddress::toSnapshot]]), so the
	 * invoice, its PDF and its QR slip read an edited address the way they
	 * read a bought one.
	 *
	 * @return array<string, string|null>
	 */
	public function snapshot(): array
	{
		$data = $this->validated();
		$optional = fn (string $key) => ($data[$key] ?? '') === '' ? null : trim((string) $data[$key]);

		return [
			'first_name' => $optional('first_name'),
			'last_name' => $optional('last_name'),
			'company' => $optional('company'),
			'street' => $optional('street'),
			'street_no' => $optional('street_no'),
			'zip' => $optional('zip'),
			'city' => $optional('city'),
			'country_code' => $data['country'],
		];
	}
}
