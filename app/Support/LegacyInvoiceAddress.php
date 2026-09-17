<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Reads a legacy frozen billing address ([[01-schema]], [[03-invoices]]).
 *
 * Legacy stored it as a rendered HTML fragment on both the booking and the
 * invoice: `Antonia Haller<br>Kaiserstr. 76<br>7752 Orsières`. There is no
 * reliable way back to fields from that — a two-word line could be a first and
 * last name or a company, and a street number may or may not be split off.
 *
 * So this keeps the lines verbatim under `lines` and does not guess. The
 * structured shape is for addresses captured from here on; the 126 historical
 * bookings and 131 historical invoices stay as the text that was actually
 * printed on the bill, which is the honest answer to "what address did we
 * invoice?".
 *
 * Shared by both ports so the answer cannot drift between the booking and the
 * invoice that was raised from it.
 */
final class LegacyInvoiceAddress
{
	/** @return array{lines: array<int, string>}|null */
	public static function parse(?string $html): ?array
	{
		if (blank($html)) {
			return null;
		}

		$lines = preg_split('/<br\s*\/?>|\r\n|\n/i', $html) ?: [];
		$lines = array_values(array_filter(array_map(
			fn (string $line) => trim(html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
			$lines,
		), 'filled'));

		return $lines === [] ? null : ['lines' => $lines];
	}
}
