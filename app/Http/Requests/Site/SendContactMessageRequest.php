<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The Kontakt form ([[04-content]], `Open-Questions.md` #24): the mockup's three
 * fields, and Cloudflare Turnstile's token ([[Turnstile]]). `website` is the
 * honeypot — hidden from people, filled by bots — and is checked by the
 * controller rather than here, so a bot is told it worked.
 */
class SendContactMessageRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'email', 'max:255'],
			'message' => ['required', 'string', 'max:5000'],
			'website' => ['nullable'],
			'cf-turnstile-response' => [new Turnstile('contact', $this->ip())],
		];
	}

	public function attributes(): array
	{
		return ['name' => 'Name', 'email' => 'E-Mail', 'message' => 'Nachricht'];
	}
}
