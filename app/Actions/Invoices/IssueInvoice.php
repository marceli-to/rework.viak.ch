<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Support\Accounting\AccountingSystem;
use App\Support\InvoiceLine;
use App\Support\InvoiceNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Writes an invoice ([[03-invoices]]).
 *
 * The only place an invoice is created. Legacy did it from inside a Mailable's
 * `build()` — `EventConfirmationStudent` created the invoice as a side effect
 * of composing the confirmation email — which meant the document a customer
 * legally owes money against came into existence when a mail template was
 * rendered, and could be created twice or not at all depending on queue
 * behaviour. Nothing about issuing a bill belongs in a mail class.
 *
 * What this does, in order:
 *
 * 1. mints the next number under a row lock, inside a transaction, because two
 *    events confirming in the same second must not share one ([[InvoiceNumber]]);
 * 2. writes the lines, each computing its own VAT from its own rate;
 * 3. stores the header totals as the sums of those lines;
 * 4. **then**, after the transaction has committed, posts to the books.
 *
 * Step 4 is outside the transaction on purpose: an HTTP call inside one holds
 * a lock open across the network, and a failed post must not roll back an
 * invoice the customer is about to be shown. If the post fails, the invoice
 * exists and is missing from the books — which is a reconciliation the admin
 * worklist can show, and the opposite mistake (books charged, no invoice) is
 * the one that cannot be repaired.
 */
class IssueInvoice
{
	public function __construct(
		private readonly InvoiceNumber $numbers,
		private readonly AccountingSystem $accounting,
	) {}

	/**
	 * @param  array<int, InvoiceLine>  $lines
	 * @param  array<string, mixed>|null  $invoiceAddress
	 */
	public function execute(
		User $user,
		array $lines,
		?Carbon $dueAt = null,
		?array $invoiceAddress = null,
		?Carbon $date = null,
	): Invoice {
		if ($lines === []) {
			throw new InvalidArgumentException('An invoice with no lines is not a document, it is a mistake.');
		}

		$invoice = DB::transaction(function () use ($user, $lines, $dueAt, $invoiceAddress, $date): Invoice {
			$invoice = Invoice::create([
				'number' => $this->numbers->nextInTransaction(),
				'user_id' => $user->id,
				'date' => $date ?? today(),
				'due_at' => $dueAt ?? today()->addDays((int) config('invoice.payment_period')),
				'invoice_address' => $invoiceAddress,
			]);

			foreach (array_values($lines) as $index => $line) {
				$item = new InvoiceItem($line->attributes($index + 1));
				$item->invoice_id = $invoice->id;
				$item->computeAmounts()->save();
			}

			$invoice->storeTotalsFromItems();

			return $invoice;
		});

		$this->accounting->record($invoice);

		return $invoice->load('items');
	}
}
