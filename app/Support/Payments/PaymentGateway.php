<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Models\Invoice;

/**
 * Paying an invoice by card ([[13-checkout]], #28). Stripe Checkout in
 * production, as legacy used: the card is taken on Stripe's page and never
 * touches this application.
 *
 * **Legacy marked an invoice paid when the browser reached its success URL**
 * (`PaymentController::success`), from an invoice number it had put in the
 * session: an abandoned payment followed by a visit to `/de/zahlung/erfolgreich`
 * settled the invoice and mailed both confirmations, and a customer who paid
 * and closed the tab left it open. Here the browser only ever hands back a
 * session id; whether it was paid is asked of the provider ([[paidSession]]),
 * and the provider's webhook says so too ([[webhookPayment]]). Either one is
 * enough, both are harmless ([[RecordCardPayment]]).
 */
interface PaymentGateway
{
	/**
	 * Opens a checkout for what the invoice still owes, and returns where to
	 * send the customer. `{CHECKOUT_SESSION_ID}` in the success URL is replaced
	 * with the session's id on the way back.
	 */
	public function checkout(Invoice $invoice, string $successUrl, string $cancelUrl): string;

	/** The payment behind a checkout session, or null while it is not paid. */
	public function paidSession(string $session): ?CardPayment;

	/**
	 * A webhook call, verified: the payment it reports, or null for an event
	 * that reports none.
	 *
	 * @throws InvalidWebhook when the call is not from the provider
	 */
	public function webhookPayment(string $payload, ?string $signature): ?CardPayment;
}
