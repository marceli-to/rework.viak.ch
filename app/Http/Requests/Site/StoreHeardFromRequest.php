<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Support\SiteUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * *Wie wurdest du auf uns aufmerksam?* ([[11-tracking]] step 4): one key of
 * `config('tracking.heard_from')`, and the text behind *Andere*, which is the
 * only answer that needs one. Sending nothing is refused rather than stored:
 * the customer can simply not send the form.
 */
class StoreHeardFromRequest extends FormRequest
{
	protected function getRedirectUrl(): string
	{
		return SiteUrl::checkout('confirmation').'#aufmerksam';
	}

	public function rules(): array
	{
		return [
			'heard_from' => ['required', 'string', Rule::in(array_keys(config('tracking.heard_from')))],
			'heard_from_other' => ['nullable', 'required_if:heard_from,andere', 'string', 'max:255'],
		];
	}

	public function messages(): array
	{
		return [
			'heard_from.required' => 'Bitte wähle eine Antwort.',
			'heard_from_other.required_if' => 'Bitte schreib kurz, wie.',
		];
	}
}
