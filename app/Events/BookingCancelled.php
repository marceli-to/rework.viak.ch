<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A seat was given up ([[06-bookings]]).
 *
 * Carries the booking with its `cancellation_reason` already set, because every
 * listener wants to know *who* cancelled: a student dropping out crosses the
 * minimum-participants threshold downwards and may owe a penalty, while VIAK
 * calling the course off does neither.
 *
 * The penalty itself is **not** a listener. It is money, so it is called
 * directly from the Action ([[CancelBooking]]).
 */
class BookingCancelled
{
	use Dispatchable;

	public function __construct(public readonly Booking $booking) {}
}
