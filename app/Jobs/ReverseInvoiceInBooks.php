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
 * Reverses a cancelled invoice in Run My Accounts ([[AccountingSystem::reverse]]).
 *
 * On the queue and retried, as [[RecordPaymentInBooks]]: the cancellation has
 * already happened here, and a network hiccup must not leave the books
 * demanding an invoice nobody owes. Until 2026-10-20 the rework sent nothing
 * at all on a cancellation, which is the same open invoice for good.
 */
class ReverseInvoiceInBooks implements ShouldQueue
{
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;

	public int $tries = 5;

	/** @var array<int, int> */
	public array $backoff = [60, 300, 900, 3600];

	public function __construct(public readonly Invoice $invoice, public readonly bool $paid)
	{
		$this->afterCommit();
	}

	public function handle(AccountingSystem $accounting): void
	{
		$accounting->reverse($this->invoice, $this->paid);
	}
}
