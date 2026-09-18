<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a generated PDF is ([[08-accounts]]).
 *
 * The two legacy values, kept verbatim as uppercase strings so the port is a
 * copy rather than a translation — the same treatment [[InvoiceStatus]] gets.
 */
enum DocumentType: string
{
	case Invoice = 'INVOICE';
	case ParticipationConfirmation = 'PARTICIPATION_CONFIRMATION';

	public function label(): string
	{
		return match ($this) {
			self::Invoice => 'Rechnung',
			self::ParticipationConfirmation => 'Teilnahmebestätigung',
		};
	}
}
