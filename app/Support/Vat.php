<?php

declare(strict_types=1);

namespace App\Support;

/**
 * VAT, to the centime ([[03-invoices]]).
 *
 * Decided 2026-09-14: Swiss practice figures VAT to the centime and rounds
 * only a *cash payment total* to 0.05. Legacy's `RentalInvoice::getVat()` did
 * `round($amount / 100 * $rate * 20) / 20` — the cash habit applied a step too
 * early, which turned 8.1 % of 80.00 from 6.48 into 6.50. The shop's basket
 * summary rounded to the centime (370.00 → 29.97) and was right.
 *
 * 80.00 is the pin: 6.48 here, 6.50 under the old rule. The 29 historical
 * rental invoices keep their 6.50 because that is what the customer was billed
 * and what the books recorded — the port copies `vat` across and never
 * recomputes it.
 *
 * Amounts are decimal strings throughout, via bcmath, for the reason every
 * money column in this schema is `decimal(8,2)`: binary floats cannot hold a
 * franc amount exactly and an invoice has to add up.
 */
final class Vat
{
	/**
	 * VAT on a net amount at a percentage rate, both decimal strings.
	 *
	 * `bcadd` truncates at the given scale, so adding half a centime first
	 * turns truncation into round-half-up — the behaviour `round()` gives on
	 * floats, without the float.
	 */
	public static function on(string $net, string $rate): string
	{
		if ($rate === '0.00' || $net === '0.00') {
			return '0.00';
		}

		$exact = bcdiv(bcmul($net, $rate, 6), '100', 6);

		return bcadd($exact, '0.005', 2);
	}

	/** Net plus its VAT — what the line adds to the invoice's grand total. */
	public static function gross(string $net, string $rate): string
	{
		return bcadd($net, self::on($net, $rate), 2);
	}
}
