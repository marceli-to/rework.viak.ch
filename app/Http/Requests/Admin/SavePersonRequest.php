<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Forms\Schema;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * What the expert and the student forms share ([[07-dashboard]], step 6): a
 * person on `users`, and their roles on `role_user`.
 */
abstract class SavePersonRequest extends FormRequest
{
	abstract protected function schema(): Schema;

	/** The person being edited; null on create. */
	abstract protected function person(): ?User;

	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From the form's one declaration. */
	public function rules(): array
	{
		return $this->schema()->rules($this->person());
	}

	public function messages(): array
	{
		return $this->schema()->messages();
	}

	public function attributes(): array
	{
		return $this->schema()->attributes();
	}

	/**
	 * An admin cannot take their own Admin role away. Nobody else could give
	 * it back from a screen they can no longer open.
	 */
	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator): void {
			if ($this->person()?->is($this->user()) && ! in_array(Role::Admin->value, (array) $this->input('roles', []), true)) {
				$validator->errors()->add('roles', 'Du kannst dir die Admin-Rolle nicht selbst entziehen.');
			}
		});
	}

	/** @return array<string, mixed> */
	public function userAttributes(): array
	{
		$data = $this->validated();
		$optional = fn (string $key) => ($data[$key] ?? '') === '' ? null : $data[$key];

		return [
			'gender' => $data['gender'],
			'first_name' => $data['first_name'],
			'last_name' => $data['last_name'],
			'company' => $optional('company'),
			'email' => $data['email'],
			'phone' => $optional('phone'),
			'street' => $data['street'],
			'street_no' => $optional('street_no'),
			'zip' => $data['zip'],
			'city' => $data['city'],
			'country_code' => $data['country'],
			'subscribe_newsletter' => (bool) ($data['subscribe_newsletter'] ?? false),
		];
	}

	/** @return array<int, Role> */
	public function roles(): array
	{
		return array_map(fn (string $role) => Role::from($role), $this->validated('roles'));
	}
}
