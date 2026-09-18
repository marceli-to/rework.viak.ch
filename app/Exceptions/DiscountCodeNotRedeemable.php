<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A code was typed in and will not apply ([[06-bookings]]).
 *
 * Exists so the customer is told. Legacy's `Discount::apply()` returned `FALSE`
 * on failure and `Booking::create()` wrote that into `discount_amount`, where
 * PHP made it 0 — so an expired code showed a discounted basket, charged full
 * price, and said nothing. Silence is the bug; this is the fix.
 */
class DiscountCodeNotRedeemable extends RuntimeException
{
	// Not `$code`: Exception already has one, and it is an int.
	public function __construct(public readonly string $discountCode)
	{
		parent::__construct("The discount code {$discountCode} is not valid, has expired, or has already been used.");
	}
}
