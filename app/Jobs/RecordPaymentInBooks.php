<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Invoice;
use App\Support\Accounting\AccountingSystem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Books a card payment ([[AccountingSystem::recordPayment]]).
 *
 * On the queue and retried, because the payment has already happened: Stripe
 * has the money and the customer has been told. Legacy posted it inline, after
 * marking the invoice paid, so a Run My Accounts hiccup left an invoice paid
 * here and open in the books for good. A failed job stays in `failed_jobs`.
 */
class RecordPaymentInBooks implements ShouldQueue
{
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;

	public int $tries = 5;

	/** @var array<int, int> */
	public array $backoff = [60, 300, 900, 3600];

	public function __construct(public readonly Invoice $invoice)
	{
		$this->afterCommit();
	}

	public function handle(AccountingSystem $accounting): void
	{
		$accounting->recordPayment($this->invoice);
	}
}
