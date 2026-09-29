<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\ProfileSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The admin's own profile on the dashboard ([[ProfileSchema]]). The rules are
 * the schema's; what is checked across fields is the portal's
 * ([[UpdateProfileRequest]]): the current password, when the address or the
 * password changes, and only then.
 */
class SaveProfileRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	public function rules(): array
	{
		return (new ProfileSchema)->rules($this->user());
	}

	public function messages(): array
	{
		return (new ProfileSchema)->messages();
	}

	public function attributes(): array
	{
		return (new ProfileSchema)->attributes();
	}

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator): void {
			if (! $this->changesEmail() && ! $this->filled('password')) {
				return;
			}

			if (! $this->filled('current_password')) {
				$validator->errors()->add('current_password', 'Bitte dein aktuelles Passwort eingeben, um E-Mail oder Passwort zu ändern.');
			} elseif (! password_verify((string) $this->input('current_password'), (string) $this->user()->password)) {
				$validator->errors()->add('current_password', 'Das Passwort ist nicht korrekt.');
			}
		});
	}

	/** The address actually moving, not resent as printed in the field. */
	public function changesEmail(): bool
	{
		return $this->filled('email') && $this->string('email')->value() !== $this->user()->email;
	}

	/** @return array<string, mixed> */
	public function profileAttributes(): array
	{
		$data = $this->validated();
		$optional = fn (string $key) => ($data[$key] ?? '') === '' ? null : $data[$key];

		return [
			'gender' => $data['gender'],
			'first_name' => $data['first_name'],
			'last_name' => $data['last_name'],
			'company' => $optional('company'),
			'phone' => $optional('phone'),
			'street' => $optional('street'),
			'street_no' => $optional('street_no'),
			'zip' => $optional('zip'),
			'city' => $optional('city'),
			'country_code' => $data['country'],
		];
	}
}
