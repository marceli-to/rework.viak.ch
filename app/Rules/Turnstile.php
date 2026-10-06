<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * A Cloudflare Turnstile token, checked with Cloudflare's `siteverify`
 * ([[ContactController]]).
 *
 * **Skipped without a secret**, so a machine without keys and the test suite
 * never call Cloudflare (`config('services.turnstile')`). **Implicit**, so an
 * empty token fails rather than being passed over, as a missing field would be.
 * **Fails closed**: if Cloudflare cannot be asked, the message is refused and
 * the visitor is told to try again.
 */
class Turnstile implements ValidationRule
{
	public bool $implicit = true;

	private const VERIFY = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

	public function __construct(private readonly ?string $ip = null) {}

	public static function enabled(): bool
	{
		return filled(config('services.turnstile.secret_key'));
	}

	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (! self::enabled()) {
			return;
		}

		$message = 'Die Sicherheitsprüfung ist fehlgeschlagen. Bitte versuche es nochmals.';

		if (! is_string($value) || $value === '') {
			$fail($message);

			return;
		}

		try {
			$verified = Http::asForm()->timeout(5)->post(self::VERIFY, array_filter([
				'secret' => config('services.turnstile.secret_key'),
				'response' => $value,
				'remoteip' => $this->ip,
			]))->json('success') === true;
		} catch (Throwable) {
			$verified = false;
		}

		if (! $verified) {
			$fail($message);
		}
	}
}
