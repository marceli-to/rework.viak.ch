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
				Rule::requiredIf(fn () => $this->changesEmail() || $this->filled('password')),
				// **`nullable` matters here, and it is the form's doing.** An
				// empty text input posts `''`, which `ConvertEmptyStringsToNull`
				// turns into `null` — so a save that changed nothing about the
				// credentials still failed, on *muss ein String sein*. An API
				// client omits the key and never met it. `required` is implicit
				// and still fires ahead of this when the address or the password
				// is actually changing.
				'nullable',
				'string',
				'current_password',
			],
		];
	}

	/**
	 * Is the address actually **moving**?
	 *
	 * Not the same question as *was an email sent* — and getting the two
	 * confused made the portal's profile form unusable: it prints the current
	 * address in the field, as an edit form does, so `filled('email')` was true
	 * on every save and a student could not change their phone number without
	 * typing their password. Legacy sidesteps it by calling the field
	 * `new_email` and leaving it blank, which is a different form rather than a
	 * different rule.
	 *
	 * Sending the address back unchanged is not a credential change, so it does
	 * not need confirming. Changing it still does. [[UpdateProfile]] asks the
	 * same question again, because it is called from places that are not this
	 * request.
	 */
	public function changesEmail(): bool
	{
		return $this->filled('email')
			&& $this->string('email')->value() !== $this->user()->email;
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
