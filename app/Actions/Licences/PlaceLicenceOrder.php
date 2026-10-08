<?php

declare(strict_types=1);

namespace App\Actions\Licences;

use App\Actions\Documents\InvoiceDocument;
use App\Actions\Invoices\IssueInvoice;
use App\Enums\InvoiceItemType;
use App\Models\LicenceOrder;
use App\Models\LicenceOrderItem;
use App\Models\LicenceVariant;
use App\Models\User;
use App\Support\InvoiceLine;
use App\Support\LicenceOrderNumber;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Places a licence order ([[05-licences]]). Today an admin entering one taken
 * by mail or phone; the shop's checkout will call the same action
 * (`13-checkout.md`).
 *
 * 1. Freezes each line: a variant's product title and shop label, article
 *    number and price, or **the free line**'s typed title and price (#36).
 * 2. **A priced order is invoiced now**, the lines at 8.1 % VAT on their net:
 *    a licence has no confirmation to wait for ([[03-invoices]]), and VIAK's
 *    manual order today "still sends a normal invoice". The PDF is made with
 *    it, so *Rechnungen* can hand it out.
 * 3. **A free order raises no invoice** and is paid when placed (*A free
 *    order*, the proposal for #35): a CHF 0 invoice would only post noise to
 *    the books. It is on the worklist all the same, since VIAK still has to
 *    get the demo from the vendor.
 *
 * Mails nothing yet: what the customer and VIAK are sent belongs to the
 * checkout's design ([[10-mail]]).
 *
 * @phpstan-type Line array{variant?: ?LicenceVariant, title?: ?string, price?: ?string, quantity: int, host?: ?string}
 */
class PlaceLicenceOrder
{
	public function __construct(
		private readonly LicenceOrderNumber $numbers,
		private readonly IssueInvoice $issue,
		private readonly InvoiceDocument $document,
	) {}

	/**
	 * @param  array<int, Line>  $lines
	 * @param  array<string, mixed>|null  $invoiceAddress  a [[UserAddress]] snapshot, null for the account's own
	 */
	public function execute(
		User $user,
		array $lines,
		?array $invoiceAddress = null,
		?string $deliveryEmail = null,
		?User $enteredBy = null,
	): LicenceOrder {
		if ($lines === []) {
			throw new InvalidArgumentException('An order with no lines orders nothing.');
		}

		$order = DB::transaction(function () use ($user, $lines, $invoiceAddress, $deliveryEmail, $enteredBy): LicenceOrder {
			$order = LicenceOrder::create([
				'number' => $this->numbers->nextInTransaction(),
				'user_id' => $user->id,
				'invoice_address' => $invoiceAddress,
				'delivery_email' => $deliveryEmail ?: null,
				'entered_by' => $enteredBy?->id,
			]);

			foreach (array_values($lines) as $index => $line) {
				$order->items()->create([...$this->frozen($line), 'position' => $index + 1]);
			}

			return $order;
		});

		$order->load('items');

		if (bccomp($order->net(), '0.00', 2) === 0) {
			$order->forceFill(['paid_at' => now()])->save();

			return $order;
		}

		$invoice = $this->issue->execute(
			user: $user,
			lines: $order->items->map(fn (LicenceOrderItem $item) => new InvoiceLine(
				type: InvoiceItemType::Licence,
				description: $item->description(),
				net: $item->net(),
				reference: $item->sku,
				itemable: $item,
			))->all(),
			invoiceAddress: $invoiceAddress,
		);

		$order->forceFill(['invoice_id' => $invoice->id])->save();
		$this->document->execute($invoice);

		return $order->setRelation('invoice', $invoice);
	}

	/**
	 * @param  Line  $line
	 * @return array<string, mixed>
	 */
	private function frozen(array $line): array
	{
		$variant = $line['variant'] ?? null;
		$quantity = max(1, (int) $line['quantity']);

		if ($variant === null) {
			return [
				'title' => trim((string) $line['title']),
				'price' => number_format((float) $line['price'], 2, '.', ''),
				'quantity' => $quantity,
			];
		}

		$variant->loadMissing('product');

		return [
			'licence_variant_id' => $variant->id,
			'title' => $variant->product->getTranslation('title', 'de').', '.$variant->shopLabel(),
			'sku' => $variant->sku,
			'host' => ($line['host'] ?? null) ?: null,
			'price' => (string) $variant->price,
			'quantity' => $quantity,
		];
	}
}
