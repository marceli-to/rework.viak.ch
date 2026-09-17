<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Invoices\SyncInvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Command;

/**
 * Asks the books about every invoice still owed ([[SyncInvoiceStatus]]).
 *
 * Replaces the legacy pair of nightly tasks — one that set `queued = 1` on all
 * pending invoices, another that took a single row off the queue per run, so
 * that clearing a backlog of twelve took twelve nights. Which invoices need
 * asking about is a query, not a column.
 */
class SyncInvoiceStatuses extends Command
{
	protected $signature = 'invoices:sync {--limit=100 : Most invoices to ask about in one run}';

	protected $description = 'Update pending invoices from the accounting system';

	public function handle(SyncInvoiceStatus $sync): int
	{
		$invoices = Invoice::pending()->orderBy('date')->limit((int) $this->option('limit'))->get();

		$changed = 0;

		foreach ($invoices as $invoice) {
			$before = $invoice->status;
			$after = $sync->execute($invoice);

			// A null answer means the books have nothing to say about this
			// invoice yet, which is not a change — the distinction matters
			// because this count is what tells somebody the sync is working.
			if ($after !== null && $after !== $before) {
				$changed++;
				$this->line("  {$invoice->number}: {$before->value} → {$after->value}");
			}
		}

		$this->components->info("Asked about {$invoices->count()} pending invoice(s), {$changed} changed.");

		return self::SUCCESS;
	}
}
