<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Rules\Turnstile;
use App\Support\SiteUrl;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Firmenschulung's *Anfrage senden* ([[TrainingController]]): the mockup's four
 * fields plus a phone number, optional (Marcel, 2026-10-07), and Turnstile under its own action. `website` is the honeypot, as on
 * Kontakt ([[SendContactMessageRequest]]).
 */
class SendTrainingEnquiryRequest extends FormRequest
{
	/**
	 * Back to the form, not "back": the browser drops the fragment from the
	 * referrer, and a refused enquiry would land at the top of the page with its errors
	 * out of sight (as the newsletter's, [[SubscribeNewsletterRequest]]).
	 */
	protected function getRedirectUrl(): string
	{
		return SiteUrl::training().'#anfrage';
	}

	public function rules(): array
	{
		return [
			'company' => ['required', 'string', 'max:255'],
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'email', 'max:255'],
			'phone' => ['nullable', 'string', 'max:50'],
			'message' => ['required', 'string', 'max:5000'],
			'website' => ['nullable'],
			'cf-turnstile-response' => [new Turnstile('training', $this->ip())],
		];
	}

	public function attributes(): array
	{
		return ['company' => 'Firma', 'name' => 'Ansprechperson', 'email' => 'E-Mail', 'phone' => 'Telefon', 'message' => 'Nachricht'];
	}
}
