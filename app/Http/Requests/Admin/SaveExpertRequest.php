<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Forms\ExpertSchema;
use App\Support\EditorHtml;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The dashboard's expert form ([[07-dashboard]], step 6), the shape
 * [[ExpertFormResource]] hands out: the person on `users`, the bio and the
 * two flags on `expert_profiles`, the roles on `role_user`.
 */
class SaveExpertRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From [[ExpertSchema]], the form's one declaration. */
	public function rules(): array
	{
		return (new ExpertSchema)->rules($this->route('expert'));
	}

	public function messages(): array
	{
		return (new ExpertSchema)->messages();
	}

	public function attributes(): array
	{
		return (new ExpertSchema)->attributes();
	}

	/**
	 * An admin cannot take their own Admin role away. Nobody else could give
	 * it back from a screen they can no longer open.
	 */
	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator): void {
			if ($this->route('expert')?->is($this->user()) && ! in_array(Role::Admin->value, (array) $this->input('roles', []), true)) {
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

	/** @return array<string, mixed> */
	public function profileAttributes(): array
	{
		$data = $this->validated();

		return [
			'title' => ($data['title'] ?? '') === '' ? null : $data['title'],
			'description' => EditorHtml::sanitize($data['description'] ?? null),
			'visible' => (bool) ($data['visible'] ?? false),
			'publish' => (bool) ($data['publish'] ?? false),
		];
	}

	/** @return array<int, Role> */
	public function roles(): array
	{
		return array_map(fn (string $role) => Role::from($role), $this->validated('roles'));
	}
}
