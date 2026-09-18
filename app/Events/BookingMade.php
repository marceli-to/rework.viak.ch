<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A seat was sold ([[06-bookings]]).
 *
 * An event rather than a direct call because what hangs off it is
 * *notification*: the participant thresholds, the booking confirmation, and the
 * messages already posted to that course. Nothing that must happen for the money
 * to be right listens to this — the money is written inside the checkout
 * transaction ([[00-foundation]]).
 */
class BookingMade
{
	use Dispatchable;

	public function __construct(public readonly Booking $booking) {}
}
