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

		/**
		 * Whether the customer already holds a booking on this event.
		 *
		 * Not a price, and it is here because it is **per line**.
		 * [[CompleteCheckout]] refuses a duplicate outright — legacy's
		 * `Booking::can()` did too — but refusing at the last step is a poor
		 * place to learn it, so the basket marks the row in red the way
		 * production does ([[09-public-site]]). Null when nobody is signed in,
		 * which is also when nobody can see this page.
		 */
		public readonly bool $booked = false,
	) {}
}
