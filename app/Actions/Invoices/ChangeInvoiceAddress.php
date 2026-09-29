<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Actions\Documents\RenderInvoice;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Bills someone else for the same thing ([[07-dashboard]], step 7): a customer
 * asks for the invoice in their firm's name, and the admin changes the address.
 *
 * **The address and nothing else.** Legacy's endpoint was
 * `$invoice->update($request->all())`, so anything an admin's browser sent
 * (an amount, a status, a number) was written to a bill a customer already
 * held. An invoice's totals are its document ([[Invoice::storeTotalsFromItems]]).
 *
 * **Only while it is still owed.** A paid invoice is in the books and a
 * cancelled one was replaced or called off; neither is sent again, so neither
 * gets a new address. Legacy's list offered the pencil on open and overdue
 * invoices only, and this is the server saying the same.
 *
 * The PDF is made again, as legacy did, so the customer's download and the
 * next mail carry the new address. **Run My Accounts is not told**: legacy did
 * not tell it either, and the booking there is by customer number, not by
 * address ([[run-my-accounts-mock-until-cutover]]).
 */
class ChangeInvoiceAddress
{
	public function __construct(private readonly RenderInvoice $render) {}

	/** @param array<string, string|null> $address [[UserAddress::toSnapshot]]'s shape */
	public function execute(Invoice $invoice, array $address): Invoice
	{
		if (! $invoice->isPending()) {
			throw new RuntimeException("Invoice {$invoice->number} is {$invoice->status->value}; only an open or overdue invoice changes its address.");
		}

		return DB::transaction(function () use ($invoice, $address): Invoice {
			$invoice->forceFill(['invoice_address' => $address])->save();
			$this->render->execute($invoice);

			return $invoice->refresh();
		});
	}
}
