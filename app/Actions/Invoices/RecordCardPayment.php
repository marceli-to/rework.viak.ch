<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Events\InvoicePaid;
use App\Jobs\RecordPaymentInBooks;
use App\Models\Invoice;
use App\Models\LicenceOrder;
use App\Support\Payments\CardPayment;
use App\Support\Payments\StripeGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Settles an invoice a card has paid ([[13-checkout]], #28): the one place an
 * invoice becomes PAID from this side rather than from the books
 * ([[SyncInvoiceStatus]]).
 *
 * **Called twice for every payment, on purpose**: from the return page, which
 * asks the provider about the session the browser came back with, and from
 * the provider's webhook, which also covers the customer who paid and closed
 * the tab. The first settles; the second finds the same `card_payment` and
 * does nothing. The row is locked while it decides, so the two cannot both
 * settle.
 *
 * Then, once: the invoice paid, a licence order on it paid, the payment booked
 * ([[RecordPaymentInBooks]]) and both told ([[InvoicePaid]]).
 *
 * What it refuses, loudly in the log and without settling: an amount or
 * currency that is not the invoice's, and a payment for an invoice that is
 * already settled some other way (paid by QR, paid by a second card session,
 * cancelled). That money is real and has to go back by hand.
 */
class RecordCardPayment
{
	public function execute(CardPayment $payment): ?Invoice
	{
		$settled = DB::transaction(function () use ($payment): ?Invoice {
			$invoice = Invoice::query()->where('uuid', $payment->invoice)->lockForUpdate()->first();

			if ($invoice === null) {
				Log::error('Card payment for an unknown invoice', $this->context($payment));

				return null;
			}

			if ($invoice->card_payment === $payment->reference) {
				return null;
			}

			if ($payment->amount !== StripeGateway::centimes((string) $invoice->grand_total) || strtolower($payment->currency) !== 'chf') {
				Log::error('Card payment does not match invoice '.$invoice->number.', not settled', $this->context($payment));

				return null;
			}

			if (! $invoice->isPending()) {
				Log::error('Card payment for invoice '.$invoice->number.', which is already '.$invoice->status->value.': refund it', $this->context($payment));

				return null;
			}

			$invoice->forceFill([
				'status' => InvoiceStatus::Paid,
				'paid_at' => now(),
				'card_payment' => $payment->reference,
			])->save();

			LicenceOrder::query()->where('invoice_id', $invoice->id)->whereNull('paid_at')->update(['paid_at' => now()]);

			return $invoice;
		});

		if ($settled !== null) {
			RecordPaymentInBooks::dispatch($settled);
			InvoicePaid::dispatch($settled);
		}

		return $settled;
	}

	/** @return array<string, mixed> */
	private function context(CardPayment $payment): array
	{
		return ['invoice' => $payment->invoice, 'amount' => $payment->amount, 'currency' => $payment->currency, 'reference' => $payment->reference];
	}
}
