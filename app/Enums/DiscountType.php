<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Replaces the legacy pair of `fix` and `percent` booleans, which could
 * represent "both" and "neither" — two states the application had no meaning
 * for and no check against.
 */
enum DiscountType: string
{
	case Fixed = 'fixed';
	case Percent = 'percent';

	/** Discount in francs off a given course fee. */
	public function applyTo(string $amount, string $fee): string
	{
		return match ($this) {
			self::Fixed => $amount,
			self::Percent => bcdiv(bcmul($fee, $amount, 4), '100', 2),
		};
	}
}
