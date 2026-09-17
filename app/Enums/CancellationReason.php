<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Why an invoice was cancelled ([[03-invoices]]).
 *
 * Legacy wrote an English sentence into `invoices.cancel_reason` and exactly
 * two of them ever occurred: "Invoice deleted because of cancelled booking"
 * (8 rows, all soft-deleted) and "Replaced by Invoice No. 000182" (6 rows,
 * the late-cancellation penalty flow, where the original is cancelled and a
 * new invoice for the penalty replaces it).
 *
 * An enum plus `invoices.replaced_by_invoice_id` says both of those without
 * having to parse prose, and the replacement becomes a link the admin can
 * follow rather than a number to search for.
 */
enum CancellationReason: string
{
	case Replaced = 'replaced';
	case BookingCancelled = 'booking_cancelled';

	/**
	 * Reads a legacy `cancel_reason` sentence. Returns null for anything
	 * unrecognised so the port reports it rather than guessing — the two known
	 * sentences cover all 14 rows, and a third would be news.
	 */
	public static function fromLegacyText(?string $text): ?self
	{
		return match (true) {
			blank($text) => null,
			str_starts_with((string) $text, 'Replaced by Invoice No.') => self::Replaced,
			str_contains((string) $text, 'cancelled booking'),
			str_contains((string) $text, 'cancelled rental') => self::BookingCancelled,
			default => null,
		};
	}
}
