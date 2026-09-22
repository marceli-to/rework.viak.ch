<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;

/**
 * An invoice address — where the bill goes when it is not the student's own
 * ([[08-accounts]]).
 *
 * ## Who it is addressed to: a person **or** a firm
 *
 * Marcel, 2026-09-22. The first cut required first and last name and left
 * company optional, which is legacy's shape and is wrong for what this is for:
 * **126 of 710 bookings** are billed to somebody other than the student, and the
 * usual reason is an employer paying. *Rechnungen, Muster AG* needs no contact
 * name, and demanding one invents a person.
 *
 * So the rule is a pair or a firm, and either alone is enough:
 *
 * - no company → both names
 * - a company → names optional
 * - one name and no company → still a failure, because half a name is not one
 *
 * `required_without` on each name gives the first three; `required_without_all`
 * on the company is what makes the *neither* case say so on the field the
 * customer is most likely to have meant.
 *
 * **The data did not force this and should not be cited as if it had.** All 122
 * ported addresses carry both names, so the old rule refused none of them; 114
 * also carry a company, which says only that the employer-paying case is the
 * norm rather than the exception. This is a rule VIAK wants going forward, and
 * the loosening is safe precisely because nothing existing depends on the
 * stricter form.
 */
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
			'first_name' => ['required_without:company', 'nullable', 'string', 'max:255'],
			'last_name' => ['required_without:company', 'nullable', 'string', 'max:255'],
			'company' => ['required_without_all:first_name,last_name', 'nullable', 'string', 'max:255'],
			'street' => ['required', 'string', 'max:255'],
			'street_no' => ['nullable', 'string', 'max:20'],
			'zip' => ['required', 'string', 'max:20'],
			'city' => ['required', 'string', 'max:255'],
			'country_code' => ['required', 'string', 'size:2', 'exists:countries,code'],
		];
	}

	/**
	 * Laravel's own wording for `required_without` names the other field —
	 * *"Vorname muss ausgefüllt sein, wenn Firma nicht ausgefüllt ist"* — which
	 * is accurate and reads like a riddle. One sentence instead, the same on all
	 * three, saying the rule rather than the branch of it that fired.
	 *
	 * @return array<string, string>
	 */
	public function messages(): array
	{
		$either = 'Bitte Vor- und Nachname oder eine Firma angeben.';

		return [
			'first_name.required_without' => $either,
			'last_name.required_without' => $either,
			'company.required_without_all' => $either,
		];
	}
}
