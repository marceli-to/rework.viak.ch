<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\InvoiceItemType;
use App\Models\LicenceVariant;

/**
 * One licence line of a priced basket ([[13-checkout]]): a variant, how many,
 * and the host it plugs into, priced the way its invoice line will be.
 *
 * **VAT per line, as [[InvoiceItem]] computes it**, not on the licences'
 * sum: the order is invoiced the moment it is placed ([[PlaceLicenceOrder]]),
 * and a total that differed from the invoice by a rounded centime would be
 * refused by Stripe's amount check ([[RecordCardPayment]]).
 */
final class BasketLicence
{
	public readonly string $net;

	public readonly string $vat;

	public function __construct(
		public readonly LicenceVariant $variant,
		public readonly int $quantity,
		public readonly ?string $host,
	) {
		$this->net = bcmul((string) $variant->price, (string) $quantity, 2);
		$this->vat = Vat::on($this->net, InvoiceItemType::Licence->vatRate());
	}

	public function total(): string
	{
		return bcadd($this->net, $this->vat, 2);
	}

	/** What [[PlaceLicenceOrder]] takes. @return array{variant: LicenceVariant, quantity: int, host: ?string} */
	public function line(): array
	{
		return ['variant' => $this->variant, 'quantity' => $this->quantity, 'host' => $this->host];
	}
}
