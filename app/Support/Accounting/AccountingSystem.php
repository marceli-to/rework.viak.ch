<?php

declare(strict_types=1);

namespace App\Support\Accounting;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

/**
 * The bookkeeping system an invoice is posted to ([[03-invoices]]).
 *
 * VIAK's books live in Run My Accounts. Every invoice this application issues
 * is created there, and payment is not something the site observes — the money
 * arrives at the bank, the accounting system sees it, and the site asks.
 *
 * This is an interface for one reason above all others, recorded as a standing
 * constraint rather than a preference: **nothing may be posted to the client's
 * live accounting from a prototype.** A rework that quietly writes invoices
 * into the books VIAK files their VAT from is the one mistake here that cannot
 * be undone. Local and testing get [[FakeAccountingSystem]]; the real client
 * is reachable only from production config, and refuses to be built without it.
 *
 * Licence sales post exactly like course sales — same entry, no special case
 * (client, 2026-09-14).
 */
interface AccountingSystem
{
	/**
	 * Create the invoice in the books.
	 *
	 * `$cancellation` posts the same invoice with a negated amount and the
	 * `_STORNO` suffix, which is how legacy reversed a bill: the books are
	 * append-only, so a withdrawn invoice is a second, opposite entry.
	 */
	public function record(Invoice $invoice, bool $cancellation = false): void;

	/**
	 * What the books say about this invoice, or null if they have nothing to
	 * say yet. This is how an invoice becomes PAID: nobody marks it by hand.
	 */
	public function statusOf(Invoice $invoice): ?InvoiceStatus;
}
