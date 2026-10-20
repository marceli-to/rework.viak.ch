<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Stripe, played locally ([[PaymentGateway]]): what every environment but
 * production gets when no Stripe test key is configured.
 *
 * The checkout sends the customer to `/dev/zahlung/{session}`, a page with
 * *Bezahlen* and *Abbrechen* ([[FakeCheckoutController]]), and the rest of the
 * flow is the real one: the return page asks this class whether the session
 * was paid, exactly as it asks Stripe. Its webhook is signed with the app key,
 * so a server that runs it cannot be paid by a stranger posting JSON.
 */
class FakePaymentGateway implements PaymentGateway
{
	private const PREFIX = 'fake-checkout:';

	public function checkout(Invoice $invoice, string $successUrl, string $cancelUrl): string
	{
		$id = 'cs_fake_'.Str::random(24);

		Cache::put(self::PREFIX.$id, [
			'invoice' => $invoice->uuid,
			'number' => $invoice->number,
			'amount' => StripeGateway::centimes((string) $invoice->grand_total),
			'success' => str_replace('{CHECKOUT_SESSION_ID}', $id, $successUrl),
			'cancel' => $cancelUrl,
			'paid' => false,
		], now()->addDay());

		return url('/dev/zahlung/'.$id);
	}

	/** @return array{invoice: string, number: string, amount: int, success: string, cancel: string, paid: bool}|null */
	public function session(string $id): ?array
	{
		return Cache::get(self::PREFIX.$id);
	}

	/** What a card payment on Stripe's page does. */
	public function pay(string $id): void
	{
		if ($session = $this->session($id)) {
			Cache::put(self::PREFIX.$id, [...$session, 'paid' => true], now()->addDay());
		}
	}

	public function paidSession(string $session): ?CardPayment
	{
		$found = $this->session($session);

		if (! $found || ! $found['paid']) {
			return null;
		}

		return new CardPayment($found['invoice'], $found['amount'], 'chf', 'pi_fake_'.substr($session, 8));
	}

	public function webhookPayment(string $payload, ?string $signature): ?CardPayment
	{
		if (! hash_equals(self::sign($payload), (string) $signature)) {
			throw new InvalidWebhook('Not signed with the app key.');
		}

		$data = json_decode($payload, true);

		return isset($data['session']) ? $this->paidSession((string) $data['session']) : null;
	}

	public static function sign(string $payload): string
	{
		return hash_hmac('sha256', $payload, (string) config('app.key'));
	}
}
