<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Events\BookingMade;
use App\Exceptions\SeatNotAvailable;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Support\BookingNumber;
use Illuminate\Support\Facades\DB;

/**
 * An admin puts someone on a course ([[06-bookings]], [[08-accounts]]).
 *
 * A real path — legacy's `Api\Dashboard\BookingController::create` — with no
 * basket, no discount and no price shown to anybody, which is why it does not
 * go through [[CompleteCheckout]] and why the booking it makes has no checkout.
 *
 * ## It does not resurrect the dead
 *
 * The one deliberate departure from legacy, and the reason this has its own
 * docblock. Legacy looked for a soft-deleted booking and then a cancelled one
 * for the same user and event, and brought it back:
 *
 *     $deletedBooking->restore();
 *     $deletedBooking->unflag('isCancelled');
 *     $deletedBooking->cancelled_at = null;
 *     $deletedBooking->booked_at = Carbon::now();
 *
 * Three things wrong with that, all of them silent:
 *
 * - the row keeps its **original `course_fee`**, frozen whenever it was first
 *   booked, so a seat re-sold two years later bills at a two-year-old price;
 * - it keeps its **original number**, so the booking sequence quietly reuses
 *   one — the same thing `withTrashed()` exists to prevent for invoices;
 * - nothing touches the **penalty invoice the cancellation raised**. Since the
 *   penalty fires automatically, cancel-then-rebook leaves a live penalty
 *   against a booking that is no longer cancelled.
 *
 * So a cancelled booking stays cancelled, with its history, and this writes a
 * new one at today's fee. If the penalty should not stand, an admin cancels that
 * invoice as a deliberate waiver, which is now a recorded act
 * ([[CancellationReason]]).
 */
class CreateBookingForUser
{
	public function __construct(private readonly BookingNumber $numbers) {}

	public function execute(Event $event, User $user, bool $rental = false): Booking
	{
		if ($event->bookings()->active()->where('user_id', $user->id)->exists()) {
			throw SeatNotAvailable::alreadyBooked($event);
		}

		$booking = DB::transaction(function () use ($event, $user, $rental): Booking {
			return Booking::create([
				'number' => $this->numbers->nextInTransaction(),
				'event_id' => $event->id,
				'user_id' => $user->id,
				'course_fee' => $event->fee(),
				'has_rental' => $rental && $event->rentals_available,
				'rental_fee' => $rental && $event->rentals_available
					? number_format((float) config('invoice.rental_fee'), 2, '.', '')
					: '0.00',
				'booked_at' => now(),
			]);
		});

		$user->forgetBookmark($event);

		event(new BookingMade($booking));

		return $booking;
	}
}
