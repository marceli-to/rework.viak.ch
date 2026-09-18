<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Events\BookingMade;
use App\Exceptions\BasketPriceChanged;
use App\Exceptions\SeatNotAvailable;
use App\Models\Booking;
use App\Models\Checkout;
use App\Models\User;
use App\Support\Basket;
use App\Support\BasketItem;
use App\Support\BookingNumber;
use Illuminate\Support\Facades\DB;

/**
 * Turns a priced basket into bookings ([[06-bookings]]).
 *
 * The thing the rework could not do. `Models\Booking` existed, chunk 01's port
 * filled it and chunk 03's `RaiseInvoiceForBooking` read it — but nothing had
 * ever *created* one: no route, no controller, no Action.
 *
 * ## What it writes, and what it deliberately does not
 *
 * A `Checkout` row, because a code discounts the order and a discount needs
 * something to be level with. Then one booking per item, each freezing the fee
 * and the rental price it was sold at.
 *
 * **No invoice.** Invoices are raised when an event is *confirmed*, a mean 27.7
 * days later, because a booking is a commitment and a course that never reaches
 * its minimum has nothing to charge for ([[03-invoices]]). The one exception is
 * a seat sold on an event that is already confirmed — chunk 03 deferred that
 * trigger and it is wired here, since this is where the sale happens.
 *
 * ## Why the discount is not split across the bookings
 *
 * It stays whole, on the checkout, and each invoice **draws down** what is left
 * ([[Checkout]]). Splitting it proportionally at this point would strand money
 * on courses that never run.
 */
class CompleteCheckout
{
	public function __construct(private readonly BookingNumber $numbers) {}

	/**
	 * @throws BasketPriceChanged when the total moved since the basket was shown
	 * @throws SeatNotAvailable when a course filled up or was called off meanwhile
	 */
	public function execute(
		User $user,
		Basket $basket,
		?string $totalShown = null,
		?array $invoiceAddress = null,
	): Checkout {
		$this->guardPrice($basket, $totalShown);

		foreach ($basket->items as $item) {
			$this->guardSeat($user, $item);
		}

		$checkout = DB::transaction(function () use ($user, $basket, $invoiceAddress): Checkout {
			$checkout = Checkout::create([
				'user_id' => $user->id,
				'discount_code_id' => $basket->discountCode?->id,
				'discount_amount' => $basket->discount,
				'net' => $basket->net(),
				'invoice_address' => $invoiceAddress,
				'completed_at' => now(),
			]);

			foreach ($basket->items as $item) {
				$booking = Booking::create([
					// Minted under a row lock inside this transaction. Legacy
					// loaded all 710 bookings and trusted primary-key order,
					// leaving a window where two checkouts in the same second
					// took the same number ([[BookingNumber]]).
					'number' => $this->numbers->nextInTransaction(),
					'event_id' => $item->event->id,
					'user_id' => $user->id,
					'checkout_id' => $checkout->id,
					'course_fee' => $item->courseFee,
					'has_rental' => $item->rental,
					'rental_fee' => $item->rentalFee,

					// Deliberately not copied onto the booking. The discount
					// belongs to the order; a per-booking copy is precisely the
					// second, disagreeing answer that cost CHF 80 in legacy.
					// The ported rows keep theirs, which is why the column
					// stays.
					'invoice_address' => $invoiceAddress,
					'booked_at' => now(),
				]);

				// A saved course you have since booked is noise. Legacy cleared
				// it from inside `Booking::create()`; same behaviour, said out
				// loud.
				$user->forgetBookmark($item->event);

				// Notifications hang off this — the participant thresholds, the
				// confirmation mail. Nothing that must happen for the money to
				// be right listens to it ([[00-foundation]]).
				event(new BookingMade($booking));
			}

			return $checkout;
		});

		return $checkout->load('bookings.event');
	}

	/**
	 * Refuse a total the customer has not seen.
	 *
	 * Skipped when the caller passes nothing, which is the admin path — an admin
	 * booking on someone's behalf has no screen to have shown a price on.
	 */
	private function guardPrice(Basket $basket, ?string $totalShown): void
	{
		if ($totalShown === null) {
			return;
		}

		$actual = $basket->total();

		if (bccomp($totalShown, $actual, 2) !== 0) {
			throw new BasketPriceChanged($totalShown, $actual);
		}
	}

	/**
	 * A seat has to still exist at the moment of sale.
	 *
	 * Legacy checked `Booking::can()` for a duplicate but never re-checked
	 * capacity or state at checkout, so a basket held open while a course filled
	 * up would sell a seat that is not there.
	 */
	private function guardSeat(User $user, BasketItem $item): void
	{
		$event = $item->event;

		if (! $event->isBookable()) {
			throw SeatNotAvailable::closed($event);
		}

		if ($event->isFull()) {
			throw SeatNotAvailable::full($event);
		}

		if ($event->bookings()->active()->where('user_id', $user->id)->exists()) {
			throw SeatNotAvailable::alreadyBooked($event);
		}
	}
}
