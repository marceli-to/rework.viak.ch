<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\InvoiceItemType;
use Illuminate\Database\Eloquent\Model;

/**
 * A line an invoice is about to be issued with ([[03-invoices]]).
 *
 * Exists so that the thing being billed — a booking today, a licence order
 * item once chunk 05 lands — is what composes its own description and price,
 * and [[IssueInvoice]] stays ignorant of both. The action's job is numbering,
 * totals and the books; it has no business knowing how a course date is
 * written out.
 *
 * `net` and `discount` are decimal strings. The VAT rate is not passed in: it
 * follows from the type, because exemption is a property of what was sold
 * ([[InvoiceItemType]]).
 */
final class InvoiceLine
{
	public function __construct(
		public readonly InvoiceItemType $type,
		public readonly string $description,
		public readonly string $net,
		public readonly string $discount = '0.00',
		public readonly ?string $reference = null,
		public readonly ?Model $itemable = null,
	) {}

	/** @return array<string, mixed> */
	public function attributes(int $position): array
	{
		return [
			'type' => $this->type,
			'description' => $this->description,
			'reference' => $this->reference,
			'position' => $position,
			'net' => $this->net,
			'discount' => $this->discount,
			'vat_rate' => $this->type->vatRate(),
			'itemable_type' => $this->itemable ? $this->itemable->getMorphClass() : null,
			'itemable_id' => $this->itemable?->getKey(),
		];
	}
}
