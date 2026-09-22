<?php

declare(strict_types=1);

namespace App\Support\Documents;

/**
 * Four rules that make the library's payment part survive dompdf
 * ([[03-invoices]]).
 *
 * `HtmlOutput` lays the slip out for a browser. Almost all of it is table cells
 * and margins, which dompdf renders correctly — **except the amount block**,
 * where `#qr-bill-payment-part-left` is a floated, fixed-width box and
 * `#qr-bill-currency` floats again inside it. dompdf cannot nest floats like
 * that: *Währung* and *Betrag* land on top of each other, and so do `CHF` and
 * the amount. Verified by rendering and reading the result, in both the SVG and
 * the PNG variant.
 *
 * A table is the one layout dompdf is reliable at, so the two cells become
 * table cells. The currency cell shrink-wraps rather than taking a percentage —
 * at 22 % of a 51 mm column *Währung* is wider than its own cell and runs into
 * *Betrag*.
 *
 * **Kept as a patch rather than a fork.** The alternative is legacy's: 251
 * lines of hand-laid slip that has to be checked against the specification by
 * hand every time the standard moves. These four rules are a dompdf quirk and
 * are labelled as one; everything about the slip that the standard governs —
 * the sizes, the wording, the languages, the corner marks — stays the
 * library's.
 */
final class PaymentSlipStyles
{
	public function forDompdf(): string
	{
		return <<<'CSS'
			<style>
			/* dompdf cannot lay out a float inside a floated, fixed-width box. */
			#qr-bill-amount-area,
			#qr-bill-amount-area-receipt { display: table; width: 100%; }

			#qr-bill-currency, #qr-bill-amount,
			#qr-bill-currency-receipt, #qr-bill-amount-receipt {
				display: table-cell; float: none; vertical-align: top;
			}

			/* Shrink-wrapped: a percentage width is narrower than the word. */
			#qr-bill-currency,
			#qr-bill-currency-receipt { width: 1%; white-space: nowrap; padding-right: 4mm; }
			</style>
			CSS;
	}
}
