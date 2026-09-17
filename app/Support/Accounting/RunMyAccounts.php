<?php

declare(strict_types=1);

namespace App\Support\Accounting;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Run My Accounts, the real thing ([[AccountingSystem]]).
 *
 * Refuses to exist without credentials rather than falling back to doing
 * nothing — a silent no-op here would mean invoices that a customer has and
 * the books do not, which is worse than an error. The service provider only
 * ever hands this out in production.
 *
 * The payload follows what legacy sent, because the books already contain 569
 * entries in this shape and an export that changes format mid-history is not
 * an export anybody can reconcile:
 *
 * - `invnumber` — `VIAK_000123`, plus `_STORNO` for a reversal
 * - `ordnumber` — the booking number, where there is one. A licence sale has
 *   no booking; the field goes out empty rather than inventing a number.
 * - `incomeentries` — one entry for the invoice total, account 3400
 * - `taxentries` — VAT, account 2201. Legacy hard-coded "0.00" here, which
 *   was true for courses and wrong for all 29 rentals; this sends the real
 *   figure, and a mixed invoice sends the sum of its taxed lines.
 */
class RunMyAccounts implements AccountingSystem
{
	public function __construct(
		private readonly string $baseUrl,
		private readonly string $apiKey,
		private readonly string $createPath,
		private readonly string $statusPath,
		private readonly string $prefix = 'VIAK_',
	) {
		if ($this->baseUrl === '' || $this->apiKey === '') {
			throw new RuntimeException('Run My Accounts is not configured. Refusing to post invoices nowhere.');
		}
	}

	public function record(Invoice $invoice, bool $cancellation = false): void
	{
		$response = Http::withToken($this->apiKey)
			->acceptJson()
			->post($this->baseUrl.$this->createPath, $this->payload($invoice, $cancellation));

		$response->throw();
	}

	public function statusOf(Invoice $invoice): ?InvoiceStatus
	{
		$url = $this->baseUrl.str_replace('%INVOICE_NO%', $this->number($invoice), $this->statusPath);

		$response = Http::withToken($this->apiKey)->acceptJson()->get($url);

		if (! $response->successful()) {
			return null;
		}

		return InvoiceStatus::tryFrom((string) $response->json('status'));
	}

	/** @return array<string, mixed> */
	private function payload(Invoice $invoice, bool $cancellation): array
	{
		$invoice->loadMissing('items.itemable', 'user');
		$amount = $cancellation ? '-'.$invoice->grand_total : (string) $invoice->grand_total;
		$vat = $cancellation ? '-'.$invoice->vat : (string) $invoice->vat;

		return [
			'invnumber' => $this->number($invoice).($cancellation ? '_STORNO' : ''),
			'ordnumber' => $invoice->items->first()?->reference ?? '',
			'currency' => 'CHF',
			'ar_accno' => '1100',
			'transdate' => $invoice->date->toIso8601String(),
			'duedate' => $invoice->due_at?->toIso8601String(),
			'description' => $invoice->items->pluck('description')->implode('; '),
			'taxincluded' => 'false',
			'customer' => [
				'customernumber' => $this->prefix.$invoice->user_id,
				'name' => $invoice->user?->name,
			],
			'incomeentries' => [
				'incomeentry' => [
					'amount' => $amount,
					'income_accno' => '3400',
					'description' => '',
				],
			],
			'taxentries' => [
				'taxentry' => [
					'amount' => $vat,
					'tax_accno' => '2201',
				],
			],
		];
	}

	private function number(Invoice $invoice): string
	{
		return $this->prefix.$invoice->number;
	}
}
