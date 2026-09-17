<?php

declare(strict_types=1);

namespace App\Support\Accounting;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

/**
 * The accounting system everywhere except production ([[AccountingSystem]]).
 *
 * Records what *would* have been posted, so tests can assert on it and a
 * developer can see it in the log, and posts nothing. This is the default
 * binding; production has to be configured out of it deliberately.
 */
class FakeAccountingSystem implements AccountingSystem
{
	/** @var array<int, array{number: string, amount: string, cancellation: bool}> */
	public array $recorded = [];

	/** @var array<string, InvoiceStatus> Statuses a test wants the books to report. */
	private array $statuses = [];

	public function record(Invoice $invoice, bool $cancellation = false): void
	{
		$this->recorded[] = [
			'number' => $invoice->number,
			'amount' => $cancellation ? '-'.$invoice->grand_total : (string) $invoice->grand_total,
			'cancellation' => $cancellation,
		];

		Log::info('Accounting (not posted): invoice '.$invoice->number.' '.($cancellation ? 'cancellation' : 'created'));
	}

	public function statusOf(Invoice $invoice): ?InvoiceStatus
	{
		return $this->statuses[$invoice->number] ?? null;
	}

	public function reports(string $number, InvoiceStatus $status): void
	{
		$this->statuses[$number] = $status;
	}

	public function recordedNumbers(): array
	{
		return array_column($this->recorded, 'number');
	}
}
