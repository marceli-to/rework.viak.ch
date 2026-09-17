<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceItemType;
use App\Support\Vat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One line on an invoice ([[03-invoices]]).
 *
 * The line is where the rework departs from legacy, which had none: a single
 * `is_rental` boolean on the invoice meant a laptop rental could not sit on the
 * same document as the course it belonged to, so a student with a rental got
 * two invoice numbers and two QR bills for one booking.
 *
 * VAT lives here rather than on the header because the rate is a property of
 * what was sold: a course line is exempt, a rental or licence line is not
 * ([[InvoiceItemType]]). An invoice's `vat` is the sum of its lines'.
 *
 * `description` and `reference` are frozen at issue. `itemable` is nullable and
 * is a convenience for navigation, not the record of what was billed — the
 * description is that, and it has to outlive the thing it describes.
 */
class InvoiceItem extends Model
{
	use HasFactory;

	protected $fillable = [
		'invoice_id', 'type', 'itemable_type', 'itemable_id',
		'description', 'reference', 'position',
		'net', 'discount', 'vat_rate', 'vat', 'total',
	];

	protected function casts(): array
	{
		return [
			'type' => InvoiceItemType::class,
			'net' => 'decimal:2',
			'discount' => 'decimal:2',
			'vat_rate' => 'decimal:2',
			'vat' => 'decimal:2',
			'total' => 'decimal:2',
		];
	}

	public function invoice(): BelongsTo
	{
		return $this->belongsTo(Invoice::class);
	}

	public function itemable(): MorphTo
	{
		return $this->morphTo();
	}

	/** What VAT applies to: the net, less any discount taken off this line. */
	public function taxableAmount(): string
	{
		return bcsub((string) $this->net, (string) $this->discount, 2);
	}

	/**
	 * Fills in `vat` and `total` from `net`, `discount` and `vat_rate`.
	 *
	 * Used when *issuing*. Ported lines never go through here: their `vat` is
	 * copied from the legacy row verbatim, including the 29 rentals whose 6.50
	 * came from 5-centime rounding this rework does not do ([[Vat]]).
	 */
	public function computeAmounts(): static
	{
		$taxable = $this->taxableAmount();
		$vat = Vat::on($taxable, (string) $this->vat_rate);

		$this->vat = $vat;
		$this->total = bcadd($taxable, $vat, 2);

		return $this;
	}
}
