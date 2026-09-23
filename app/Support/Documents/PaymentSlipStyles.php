<?php

declare(strict_types=1);

namespace App\Support\Documents;

/**
 * Six rules that make the library's payment part survive dompdf
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
 * hand every time the standard moves. These rules are dompdf quirks and
 * are labelled as such; everything about the slip that the standard governs —
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

			/* dompdf ignores `box-sizing: border-box` and adds the padding to
			   the library's 62 / 148mm, which put the cut line at 67mm and ran
			   the slip to 225mm. The content widths, so the boxes come out at
			   the standard's. */
			#qr-bill-receipt { width: 57mm; }
			#qr-bill-payment-part { width: 138mm; }

			/* The slip is 105mm tall and the library leaves it to its content,
			   which stops the vertical cut line 5mm short of the edge. 5mm of
			   padding plus this, with half a millimetre spare so rounding cannot
			   push the slip onto a third page. */
			#qr-bill-receipt, #qr-bill-payment-part { height: 99.5mm; }
			</style>
			CSS;
	}
}
