<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;

/**
 * One line of a priced basket: an event, whether a laptop was asked for, and
 * what both cost **at this instant**.
 *
 * The fees are captured here and frozen onto the booking, because the invoice is
 * raised a mean 27.7 days later and a rate change in between must not reach a
 * customer who was quoted the old price.
 */
final class BasketItem
{
	public function __construct(
		public readonly Event $event,
		public readonly bool $rental,
		public readonly string $courseFee,
		public readonly string $rentalFee,
	) {}
}
