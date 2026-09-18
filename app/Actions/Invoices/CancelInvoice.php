<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\CancellationReason;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use RuntimeException;

/**
 * Withdraws an invoice, with a reason ([[06-bookings]]).
 *
 * Legacy set `status = CANCELLED` wherever it happened to be convenient and
 * wrote an English sentence into `cancel_reason` — or, for the two deliberate
 * penalty waivers, wrote nothing at all. Those two NULLs are the only
 * unexplained cancellations in 569 invoices, which means the single most
 * human decision in the whole flow was the one thing the system did not record.
 *
 * So cancelling is a first-class action and the reason is required. That also
 * gives the admin worklist chunk 03 deferred something concrete to call.
 *
 * An invoice is a document that was sent. Cancelling does not delete it, edit
 * its lines or change its total: it marks it withdrawn and, where something
 * supersedes it, links to the replacement.
 */
class CancelInvoice
{
	public function execute(
		Invoice $invoice,
		CancellationReason $reason,
		?Invoice $replacedBy = null,
	): Invoice {
		if ($invoice->isCancelled()) {
			return $invoice;
		}

		if ($reason === CancellationReason::Replaced && $replacedBy === null) {
			throw new RuntimeException("Invoice {$invoice->number} cannot be cancelled as *replaced* without the invoice that replaces it — that is the whole difference between this and an unexplained cancellation.");
		}

		$invoice->forceFill([
			'status' => InvoiceStatus::Cancelled,
			'cancelled_at' => now(),
			'cancellation_reason' => $reason,
			'replaced_by_invoice_id' => $replacedBy?->id,
		])->save();

		return $invoice;
	}
}
