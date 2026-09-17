<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What an invoice line is for — and, with it, whether VAT applies
 * ([[03-invoices]]).
 *
 * The VAT rule lives here because it is a property of the *thing sold*, not of
 * the invoice or the customer: Swiss VAT exempts education
 * (*Bildungsleistungen*), so a course line is 0.00 while a laptop rental and a
 * software licence are taxed at the standard rate.
 *
 * Legacy had this as `invoices.is_rental`, a boolean, with the VAT hard-zeroed
 * in `BasketController::getTotals()` under a `@todo: fix vat on event`. The
 * zero was the correct answer all along; the todo was not. **Do not "fix" the
 * zero on courses.**
 */
enum InvoiceItemType: string
{
	case Course = 'COURSE';
	case Rental = 'RENTAL';
	case Licence = 'LICENCE';

	/** The rate this kind of line is taxed at, as a percentage. */
	public function vatRate(): string
	{
		return match ($this) {
			self::Course => '0.00',
			self::Rental, self::Licence => number_format((float) config('invoice.vat_rate'), 2, '.', ''),
		};
	}
}
