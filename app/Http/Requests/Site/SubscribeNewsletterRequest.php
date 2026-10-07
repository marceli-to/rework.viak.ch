<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Rules\Turnstile;
use App\Support\SiteUrl;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The homepage footer's newsletter form ([[NewsletterController]]): legacy's
 * three fields (`frontend/newsletter/Index.vue`), and Turnstile and a
 * honeypot as on the other public forms.
 */
class SubscribeNewsletterRequest extends FormRequest
{
	/**
	 * Back to the form, not "back": the browser drops the fragment from the
	 * referrer, and a refused signup would land at the top of the page with
	 * the form shut and its errors out of sight.
	 */
	protected function getRedirectUrl(): string
	{
		return SiteUrl::home().'#newsletter';
	}

	public function rules(): array
	{
		return [
			'firstname' => ['required', 'string', 'max:255'],
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'email', 'max:255'],
			'website' => ['nullable'],
			'cf-turnstile-response' => [new Turnstile('newsletter', $this->ip())],
		];
	}

	public function attributes(): array
	{
		return ['firstname' => 'Vorname', 'name' => 'Nachname', 'email' => 'E-Mail'];
	}
}
