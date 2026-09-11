<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Content languages. German is the primary locale; English is currently
 * admin-only on the public site (legacy gates `/en` behind `role:admin`).
 */
enum Locale: string
{
	case De = 'de';
	case En = 'en';

	/** @return array<int, string> */
	public static function values(): array
	{
		return array_map(static fn (self $c): string => $c->value, self::cases());
	}
}
