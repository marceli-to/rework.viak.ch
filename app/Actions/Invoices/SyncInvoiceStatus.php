<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Support\Accounting\AccountingSystem;

/**
 * Asks the books whether an invoice has been paid ([[AccountingSystem]]).
 *
 * Payment is not something this application observes: the money arrives at the
 * bank, Run My Accounts matches it against the QR reference, and the site asks.
 * Nobody marks an invoice paid by hand, which is why there is no action to do
 * so — the books are the source of truth for money, the same way `events.state`
 * is for a course.
 *
 * Legacy carried a `queued` boolean on every invoice and a pair of nightly
 * tasks that set it on all pending invoices and then cleared it one row at a
 * time. That column is gone: which invoices still need asking about is a query
 * (`Invoice::pending()`), not a state worth storing on a document, and all 569
 * rows in the dump have it at 0 anyway.
 */
class SyncInvoiceStatus
{
	public function __construct(private readonly AccountingSystem $accounting) {}

	/** Returns the status it settled on, or null if the books had nothing. */
	public function execute(Invoice $invoice): ?InvoiceStatus
	{
		$status = $this->accounting->statusOf($invoice);

		if ($status === null || $status === $invoice->status) {
			return $status;
		}

		$invoice->status = $status;

		// Stamped once, when the books first report it. Re-syncing a paid
		// invoice must not move the date it was paid on.
		if ($status === InvoiceStatus::Paid && $invoice->paid_at === null) {
			$invoice->paid_at = now();
		}

		// Written with `save()` and nothing else: in legacy, any write to an
		// invoice row silently reset `due_at` to now, so this very sync is
		// what destroyed the payment deadline on all 541 paid invoices.
		$invoice->save();

		return $status;
	}
}
