<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Enums\BookingCancellationReason;
use App\Models\Event;

/**
 * VIAK calls a course off ([[06-bookings]]).
 *
 * Called **directly** from [[SetEventState]], not hung off an event. Legacy made
 * it a listener (`EventCancelledHandler`), and it is exactly the case
 * `00-foundation.md` says not to: if this silently fails to run, students keep
 * seats on a course that is not happening and keep invoices for it. That is not
 * a notification, it is the consequence.
 *
 * Every booking is cancelled as `EventCancelled`, which never charges a penalty.
 * That distinction is the single most valuable thing this chunk writes down:
 * **115 of the 143 bookings on VIAK-cancelled courses fall inside the 0–10 day
 * window**, so a version that treated these like a student dropping out would
 * invoice 115 people the full fee for a course that never ran. In legacy the
 * only thing preventing that was the fact that two functions never called each
 * other ([[BookingCancellationReason]]).
 */
class CancelBookingsForEvent
{
	public function __construct(private readonly CancelBooking $cancel) {}

	/** Returns how many seats were given up. */
	public function execute(Event $event): int
	{
		$bookings = $event->bookings()->active()->get();

		foreach ($bookings as $booking) {
			$this->cancel->execute($booking, BookingCancellationReason::EventCancelled);
		}

		return $bookings->count();
	}
}
