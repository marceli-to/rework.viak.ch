<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Actions\Invoices\RecordCardPayment;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\Payments\InvalidWebhook;
use App\Support\Payments\PaymentGateway;
use App\Support\SiteUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Paying an invoice by card ([[13-checkout]], #28), legacy's
 * `PaymentController` with its two holes closed ([[PaymentGateway]]):
 *
 * - the return page settles nothing on the browser's word. It asks the
 *   provider about the session id it came back with, and the webhook settles
 *   the payment of a customer who never comes back;
 * - every step checks the invoice is the signed-in account's own. Legacy's
 *   page did, its *Weiter zur Zahlung* did not.
 *
 * And it serves any invoice, a licence order's too: legacy built Stripe's line
 * from the invoice's booking, which a licence invoice has none of.
 */
class PaymentController extends Controller
{
	public function __construct(private readonly PaymentGateway $gateway) {}

	/** The invoice, or why there is nothing to pay; `?abgebrochen` when back from Stripe's *Zurück*. */
	public function show(Request $request, Invoice $invoice): View
	{
		$this->own($request, $invoice);

		return view('site.payment.invoice', [
			'invoice' => $invoice->load('items'),
			'cancelled' => $request->has('abgebrochen'),
		]);
	}

	/** *Weiter zur Zahlung*: a Stripe session for what the invoice owes, and off to it. */
	public function start(Request $request, Invoice $invoice): RedirectResponse
	{
		$this->own($request, $invoice);

		if (! $invoice->isPending()) {
			return redirect(SiteUrl::invoicePayment($invoice->uuid));
		}

		return redirect()->away($this->gateway->checkout(
			$invoice,
			url(SiteUrl::paymentSuccess()).'?session_id={CHECKOUT_SESSION_ID}',
			url(SiteUrl::invoicePayment($invoice->uuid)).'?abgebrochen',
		));
	}

	/**
	 * Back from Stripe. The session is asked about, and settled if paid
	 * ([[RecordCardPayment]] does nothing the second time, when the webhook
	 * has been first). Not paid yet, which Stripe allows for a moment: the page
	 * says the confirmation follows, and the webhook does the rest.
	 */
	public function success(Request $request, RecordCardPayment $record): View
	{
		$payment = $request->filled('session_id') ? $this->gateway->paidSession((string) $request->query('session_id')) : null;

		if ($payment !== null) {
			$record->execute($payment);
		}

		$invoice = $payment ? Invoice::query()->where('uuid', $payment->invoice)->first() : null;

		abort_if($invoice !== null && $invoice->user_id !== $request->user()->id, 404);

		return view('site.payment.success', ['invoice' => $invoice?->isPaid() ? $invoice : null]);
	}

	/**
	 * Stripe's webhook. 400 for a call it did not sign, 200 for everything it
	 * did, even one this cannot settle: a refusal is logged by the action, and
	 * Stripe retrying it for three days would change nothing.
	 */
	public function webhook(Request $request, RecordCardPayment $record): Response
	{
		try {
			$payment = $this->gateway->webhookPayment($request->getContent(), $request->header('Stripe-Signature'));
		} catch (InvalidWebhook) {
			return response('', 400);
		}

		if ($payment !== null) {
			$record->execute($payment);
		}

		return response('', 200);
	}

	/** Somebody else's invoice is a 404: not even that the uuid exists is theirs to learn. */
	private function own(Request $request, Invoice $invoice): void
	{
		abort_if($invoice->user_id !== $request->user()->id, 404);
	}
}
