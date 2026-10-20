<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Stripe Checkout ([[PaymentGateway]]), what legacy's `PaymentController`
 * used: one line for the invoice's total, card only, Stripe's own page.
 *
 * Legacy named the line after the booking's course, which a licence invoice
 * does not have; this names it after the invoice and lists its lines, so the
 * same page settles a course invoice and a licence order.
 */
class StripeGateway implements PaymentGateway
{
	private readonly StripeClient $stripe;

	public function __construct(string $secret, private readonly string $webhookSecret)
	{
		$this->stripe = new StripeClient($secret);
	}

	public function checkout(Invoice $invoice, string $successUrl, string $cancelUrl): string
	{
		$invoice->loadMissing('items', 'user');

		$session = $this->stripe->checkout->sessions->create([
			'mode' => 'payment',
			'submit_type' => 'pay',
			'payment_method_types' => ['card'],
			'locale' => 'de',
			'customer_email' => $invoice->user?->email,
			'client_reference_id' => $invoice->uuid,
			'metadata' => ['invoice' => $invoice->uuid, 'number' => $invoice->number],
			'payment_intent_data' => ['description' => 'Rechnung '.$invoice->number],
			'line_items' => [[
				'quantity' => 1,
				'price_data' => [
					'currency' => 'chf',
					'unit_amount' => self::centimes((string) $invoice->grand_total),
					'product_data' => [
						'name' => 'Rechnung '.$invoice->number,
						'description' => $invoice->items->map(fn (InvoiceItem $item) => $item->description)->implode('; '),
					],
				],
			]],
			'success_url' => $successUrl,
			'cancel_url' => $cancelUrl,
		]);

		return (string) $session->url;
	}

	public function paidSession(string $session): ?CardPayment
	{
		return $this->payment($this->stripe->checkout->sessions->retrieve($session));
	}

	public function webhookPayment(string $payload, ?string $signature): ?CardPayment
	{
		try {
			$event = Webhook::constructEvent($payload, (string) $signature, $this->webhookSecret);
		} catch (SignatureVerificationException|UnexpectedValueException $e) {
			throw new InvalidWebhook($e->getMessage(), previous: $e);
		}

		if ($event->type !== 'checkout.session.completed') {
			return null;
		}

		return $this->payment($event->data->object);
	}

	/** In centimes, as Stripe counts: legacy's `(int) ($total * 100)`, without the float. */
	public static function centimes(string $amount): int
	{
		return (int) bcmul($amount, '100', 0);
	}

	private function payment(Session $session): ?CardPayment
	{
		if ($session->payment_status !== 'paid' || ! $session->client_reference_id) {
			return null;
		}

		return new CardPayment(
			invoice: (string) $session->client_reference_id,
			amount: (int) $session->amount_total,
			currency: (string) $session->currency,
			reference: (string) ($session->payment_intent ?: $session->id),
		);
	}
}
