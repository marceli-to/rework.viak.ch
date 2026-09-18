<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use App\Enums\Gender;
use App\Enums\OperatingSystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Editing your own details ([[08-accounts]]).
 *
 * `current_password` is required whenever an email address or a password is
 * changed, and the rule says so rather than leaving it to the Action — legacy
 * asked for it nowhere, on any of its three role-specific copies of this form.
 */
class UpdateProfileRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user() !== null;
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			'first_name' => ['required', 'string', 'max:255'],
			'last_name' => ['required', 'string', 'max:255'],
			'company' => ['nullable', 'string', 'max:255'],
			'street' => ['nullable', 'string', 'max:255'],
			'street_no' => ['nullable', 'string', 'max:20'],
			'zip' => ['nullable', 'string', 'max:20'],
			'city' => ['nullable', 'string', 'max:255'],
			'phone' => ['nullable', 'string', 'max:50'],
			'gender' => ['nullable', Rule::enum(Gender::class)],
			'country_code' => ['nullable', 'string', 'size:2', 'exists:countries,code'],
			'operating_systems' => ['nullable', 'array'],
			'operating_systems.*' => [Rule::enum(OperatingSystem::class)],

			'email' => [
				'nullable', 'email', 'max:255',
				Rule::unique('users', 'email')->ignore($this->user()->id),
			],
			'password' => ['nullable', 'string', 'min:8', 'confirmed'],

			// The check legacy never made. Without it a session left open on a
			// shared machine is enough to take an account over permanently.
			//
			// `current_password` is Laravel's own rule and it verifies the hash,
			// so a wrong password is a 422 against this field rather than an
			// exception the customer sees as a server error. The Action checks
			// again — it is called from places that are not this request.
			'current_password' => [
				Rule::requiredIf(fn () => $this->filled('email') || $this->filled('password')),
				'string',
				'current_password',
			],
		];
	}

	/** @return array<string, mixed> */
	public function profileAttributes(): array
	{
		return array_filter(
			$this->only([
				'first_name', 'last_name', 'company', 'street', 'street_no',
				'zip', 'city', 'phone', 'gender', 'country_code', 'operating_systems',
			]),
			fn ($value) => $value !== null,
		);
	}
}
