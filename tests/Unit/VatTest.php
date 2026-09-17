<?php

declare(strict_types=1);

use App\Enums\InvoiceItemType;
use App\Support\Vat;

/**
 * The regression pin for the 2026-09-14 decision ([[03-invoices]]).
 *
 * 80.00 is the amount that tells the two rules apart: 8.1 % of it is 6.48 to
 * the centime and 6.50 rounded to 0.05. Legacy's `RentalInvoice::getVat()` did
 * the latter — `round($x * 20) / 20`, the cash-rounding habit applied a step
 * too early — and every one of the 29 rental invoices ever sent says 6.50.
 * The shop's own basket summary rounded to the centime and was right.
 */
it('computes VAT to the centime, not to five', function () {
	expect(Vat::on('80.00', '8.10'))->toBe('6.48');
});

it('matches the basket summary the client confirmed the rate against', function () {
	// Gesamtnettosumme 370.00 → zzgl. 8.1 % MwSt. 29.97 → Gesamtsumme 399.97.
	expect(Vat::on('370.00', '8.10'))->toBe('29.97')
		->and(Vat::gross('370.00', '8.10'))->toBe('399.97');
});

it('rounds half a centime up', function () {
	// 8.1 % of 55.00 is 4.455 exactly.
	expect(Vat::on('55.00', '8.10'))->toBe('4.46');
});

/**
 * Swiss VAT exempts education, so a course line is 0.00 — the legacy code
 * hard-zeroed it under a `@todo: fix vat on event`, and the zero was the
 * correct answer all along.
 */
it('exempts courses and taxes rentals and licences', function () {
	expect(InvoiceItemType::Course->vatRate())->toBe('0.00')
		->and(InvoiceItemType::Rental->vatRate())->toBe('8.10')
		->and(InvoiceItemType::Licence->vatRate())->toBe('8.10');
});

it('charges nothing on an exempt line, whatever the amount', function () {
	expect(Vat::on('1295.00', '0.00'))->toBe('0.00');
});
