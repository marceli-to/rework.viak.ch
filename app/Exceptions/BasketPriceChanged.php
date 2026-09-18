<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * The price moved between showing the basket and confirming it
 * ([[06-bookings]]).
 *
 * A course fee can be edited, an event can sell out, a code can be used up by
 * somebody else — all while a basket sits open in a tab. Legacy had no check at
 * all: whatever the price was at `Booking::create()` is what the customer got,
 * higher or lower than what they had agreed to.
 *
 * So the client sends back the total it displayed, and a checkout whose price
 * has moved is **refused** rather than quietly completed at the new number.
 */
class BasketPriceChanged extends RuntimeException
{
	public function __construct(public readonly string $shown, public readonly string $actual)
	{
		parent::__construct("The basket was shown at {$shown} and now prices at {$actual}. Refusing to charge a total the customer has not seen.");
	}
}
