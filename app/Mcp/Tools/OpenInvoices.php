<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * What is still owed: *Rechnungen* filtered to open and overdue
 * ([[Invoice::scopePending]], [[17-mcp]]). Oldest due date first, the order
 * a reminder goes out in.
 */
#[Description('Invoices not yet paid (open or overdue): number, customer, date, due date, days overdue, amount in CHF, and the total owed.')]
#[IsReadOnly]
class OpenInvoices extends Tool
{
	public function handle(Request $request): Response
	{
		$input = $request->validate(['overdue_only' => ['nullable', 'boolean']]);

		$invoices = Invoice::query()
			->when(
				(bool) ($input['overdue_only'] ?? false),
				fn ($query) => $query->overdueOn(today()),
				fn ($query) => $query->pending(),
			)
			->with('user', 'company')
			->orderBy('due_at')
			->get();

		return Response::json([
			'count' => $invoices->count(),
			'total' => $invoices->reduce(fn (string $sum, Invoice $invoice) => bcadd($sum, (string) $invoice->grand_total, 2), '0.00'),
			'invoices' => $invoices->map(fn (Invoice $invoice) => [
				'number' => $invoice->number,
				'customer' => $invoice->customerName(),
				'company' => $invoice->user?->company ?? $invoice->company?->company,
				'email' => $invoice->user?->email,
				'date' => $invoice->date?->toDateString(),
				'due_at' => $invoice->due_at?->toDateString(),
				'days_overdue' => $invoice->due_at?->isPast() && ! $invoice->due_at->isToday()
					? (int) $invoice->due_at->diffInDays(today())
					: 0,
				'status' => $invoice->status === InvoiceStatus::Overdue ? 'overdue' : 'open',
				'amount' => (string) $invoice->grand_total,
			])->values()->all(),
		]);
	}

	public function schema(JsonSchema $schema): array
	{
		return [
			'overdue_only' => $schema->boolean()->description('Only invoices past their due date.'),
		];
	}
}
