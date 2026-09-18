<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Enums\DiscountType;
use App\Exceptions\DiscountCodeNotRedeemable;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\User;
use App\Support\Basket;
use App\Support\BasketItem;

/**
 * Prices a basket, server-side ([[06-bookings]]).
 *
 * Takes what the customer *selected* — event uuids and whether they want a
 * laptop — and returns what it *costs*. Nothing about the price comes from the
 * client.
 *
 * ## One code discounts the order
 *
 * Decided by Marcel on 2026-09-17, against legacy's two contradictory readings.
 * A fixed CHF 50 code takes CHF 50 off the checkout, once, however many courses
 * are in it — the basket screen was right and `Booking::create()` was wrong.
 *
 * The discount is computed against the **courses**, not the laptop rentals: a
 * course discount code is for courses, and letting one eat an CHF 80 laptop is
 * a rule nobody has ever stated. Percentage codes apply to the same base, which
 * keeps them identical to legacy — a rate across the course total is the same
 * number as the rate applied per course and summed, which is exactly why the
 * fixed-amount bug stayed invisible for three years.
 *
 * ## And it cannot exceed what it discounts
 *
 * Clamped to the course total here, and clamped again per invoice when the
 * discount is drawn down ([[Checkout]]). Booking 000512 is the reason: a fixed
 * CHF 648 code against a CHF 499 course, producing invoice 000419 at −149.00,
 * open in the live books today. A customer cannot be owed money by a course
 * they bought.
 */
class PriceBasket
{
	/**
	 * @param  array<int, array{event: Event, rental?: bool}>  $selections
	 *
	 * @throws DiscountCodeNotRedeemable when a code was typed in and will not apply
	 */
	public function execute(array $selections, ?string $code = null, ?User $for = null): Basket
	{
		$items = array_map(
			fn (array $selection) => $this->item($selection['event'], (bool) ($selection['rental'] ?? false)),
			array_values($selections),
		);

		$discountCode = $this->resolve($code);
		$basket = Basket::of($items, $discountCode, '0.00');

		if ($discountCode === null) {
			return $basket;
		}

		return Basket::of($items, $discountCode, $this->discountFor($discountCode, $basket->courseNet()));
	}

	private function item(Event $event, bool $rental): BasketItem
	{
		// Both prices are read now and frozen onto the booking. `rentals_available`
		// is the event's own switch: a course in a room without machines cannot
		// sell a laptop, and silently dropping the request would bill the
		// customer for something they asked for and did not get.
		$wantsRental = $rental && $event->rentals_available;

		return new BasketItem(
			event: $event,
			rental: $wantsRental,
			courseFee: $event->fee(),
			rentalFee: $wantsRental ? number_format((float) config('invoice.rental_fee'), 2, '.', '') : '0.00',
		);
	}

	/**
	 * A code that will not apply is an error, never a silent zero.
	 *
	 * `Discount::apply()` returned `FALSE` on failure and `Booking::create()`
	 * wrote it into `discount_amount` as 0. The customer saw a discounted
	 * basket, paid full price, and was told nothing.
	 */
	private function resolve(?string $code): ?DiscountCode
	{
		if (blank($code)) {
			return null;
		}

		$discountCode = DiscountCode::query()->where('code', $code)->first();

		if ($discountCode === null || ! $discountCode->isRedeemableOn(today())) {
			throw new DiscountCodeNotRedeemable((string) $code);
		}

		return $discountCode;
	}

	/** Never more than the courses are worth. */
	private function discountFor(DiscountCode $code, string $courseNet): string
	{
		$discount = $code->type === DiscountType::Percent
			? $code->amountOff($courseNet)
			: (string) $code->amount;

		return bccomp($discount, $courseNet, 2) > 0 ? $courseNet : $discount;
	}
}
