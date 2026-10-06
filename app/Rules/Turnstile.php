<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * A Cloudflare Turnstile token, checked with Cloudflare's `siteverify`
 * ([[ContactController]]), as Cloudflare's own integration guide has it
 * (Turnstile Spin, *canonical server-side siteverify*): the token must be a
 * string of at most 2048 characters, and the answer must say `success`, carry
 * **this form's action**, and come from **one of this deployment's hostnames**
 * (`services.turnstile.hostnames`).
 *
 * **A token is used once, and this makes sure of it.** Tested against the
 * live widget on 2026-10-06, `siteverify` answered `success` to the same token
 * twice, where Cloudflare's guide expects a replay to be refused. So each
 * token is remembered for its five-minute life and a second use is refused
 * before Cloudflare is asked. The form navigates away after sending, so the
 * widget never needs resetting.
 *
 * **Skipped without a secret**, so a machine without keys and the test suite
 * never call Cloudflare. **Implicit**, so an empty token fails rather than
 * being passed over. **Fails closed**: no hostnames configured, a non-2xx
 * answer or no answer within ten seconds all refuse the message.
 *
 * **Cloudflare's test secret** answers `example.com` and no action, whatever
 * the token, so outside production it is taken at its `success` alone. In
 * production that secret is refused outright.
 */
class Turnstile implements ValidationRule
{
	public bool $implicit = true;

	private const VERIFY = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

	/** Cloudflare's documented test secrets: always pass, always fail, token spent. */
	private const TEST_SECRETS = [
		'1x0000000000000000000000000000000AA',
		'2x0000000000000000000000000000000AA',
		'3x0000000000000000000000000000000AA',
	];

	public function __construct(
		private readonly string $action,
		private readonly ?string $ip = null,
	) {}

	public static function enabled(): bool
	{
		return filled(config('services.turnstile.secret_key'));
	}

	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (! self::enabled()) {
			return;
		}

		if (! $this->verified($value)) {
			$fail('Die Sicherheitsprüfung ist fehlgeschlagen. Bitte versuche es nochmals.');
		}
	}

	private function verified(mixed $token): bool
	{
		$secret = (string) config('services.turnstile.secret_key');
		$testing = in_array($secret, self::TEST_SECRETS, true);
		$hostnames = (array) config('services.turnstile.hostnames');

		if (! is_string($token) || $token === '' || strlen($token) > 2048) {
			return false;
		}

		// Spent here, whatever Cloudflare then says: a token is never worth a second try.
		if (! Cache::add('turnstile:'.hash('sha256', $token), true, now()->addMinutes(5))) {
			return false;
		}

		if ($testing && app()->isProduction()) {
			return false;
		}

		if (! $testing && $hostnames === []) {
			return false;
		}

		try {
			$response = Http::asForm()->timeout(10)->post(self::VERIFY, array_filter([
				'secret' => $secret,
				'response' => $token,
				'remoteip' => $this->ip,
			]));
		} catch (Throwable) {
			return false;
		}

		if (! $response->successful() || $response->json('success') !== true) {
			return false;
		}

		return $testing || (
			$response->json('action') === $this->action
			&& in_array($response->json('hostname'), $hostnames, true)
		);
	}
}
