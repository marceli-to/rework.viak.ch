<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An invoice was paid by card ([[RecordCardPayment]]), legacy's event of the
 * same name and, as in legacy, only for a card: a bank payment is something the
 * books report later ([[SyncInvoiceStatus]]) and nobody is mailed about it.
 */
class InvoicePaid
{
	use Dispatchable;

	public function __construct(public readonly Invoice $invoice) {}
}
