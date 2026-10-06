<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Firmenschulung's *Anfrage senden* ([[TrainingController]]): the mockup's four
 * fields, and Turnstile under its own action. `website` is the honeypot, as on
 * Kontakt ([[SendContactMessageRequest]]).
 */
class SendTrainingEnquiryRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'company' => ['required', 'string', 'max:255'],
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'email', 'max:255'],
			'message' => ['required', 'string', 'max:5000'],
			'website' => ['nullable'],
			'cf-turnstile-response' => [new Turnstile('training', $this->ip())],
		];
	}

	public function attributes(): array
	{
		return ['company' => 'Firma', 'name' => 'Ansprechperson', 'email' => 'E-Mail', 'message' => 'Nachricht'];
	}
}
