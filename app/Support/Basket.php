<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\InvoiceItemType;
use App\Exceptions\BasketPriceChanged;
use App\Models\DiscountCode;
use App\Models\Event;

/**
 * A basket as the **server** prices it ([[06-bookings]]).
 *
 * The client never supplies a price. `00-foundation.md` says so for `Stores/`
 * generally; this is where it is enforced, and legacy shows why it has to be.
 *
 * Three separate defects lived in that seam:
 *
 * - `BasketController::getTotals()` applied a code to the **basket total** while
 *   `Booking::create()` applied it again **per event**. For a percentage code
 *   the two agree, which is why nobody noticed for three years. For a fixed
 *   CHF 50 code across two courses the customer agreed to 1398.00 and was billed
 *   1348.00. Three real baskets, CHF 80 given away.
 * - Nothing clamped the discount to the fee. Booking 000512 spent a fixed CHF
 *   648 code on a CHF 499 course and produced **invoice 000419 at −149.00,
 *   still OPEN in the live books**.
 * - `Discount::apply()` returned `FALSE` when validation failed and
 *   `Booking::create()` wrote that straight into `discount_amount`, where it
 *   became 0. **An expired code failed silently**: the customer saw a discounted
 *   basket, was charged full price, and was told nothing.
 *
 * So: one place computes the price, from the event's own fee and the code as it
 * is at that instant, and a code that will not apply is an error the customer
 * is shown rather than a zero they are not.
 */
final class Basket
{
	/**
	 * @param  array<int, BasketItem>  $items  the courses
	 * @param  array<int, BasketLicence>  $licences
	 */
	private function __construct(
		public readonly array $items,
		public readonly ?DiscountCode $discountCode,
		public readonly string $discount,
		public readonly array $licences,
	) {}

	/**
	 * @param  array<int, BasketItem>  $items
	 * @param  array<int, BasketLicence>  $licences
	 */
	public static function of(array $items, ?DiscountCode $code, string $discount, array $licences = []): self
	{
		return new self(array_values($items), $code, $discount, array_values($licences));
	}

	/** The courses, before any discount. */
	public function courseNet(): string
	{
		return $this->sum(fn (BasketItem $item) => $item->courseFee);
	}

	/** The laptop rentals, before VAT. */
	public function rentalNet(): string
	{
		return $this->sum(fn (BasketItem $item) => $item->rentalFee);
	}

	public function net(): string
	{
		return bcadd($this->courseNet(), $this->rentalNet(), 2);
	}

	/**
	 * VAT on the basket, which in practice is VAT on the laptop rentals.
	 *
	 * Courses are exempt — Swiss VAT exempts education, 532 of 561 live invoices
	 * carry 0.00, and the legacy `@todo: fix vat on event` was wrong about its
	 * own zero ([[InvoiceItemType]]).
	 */
	public function vat(): string
	{
		return Vat::on($this->rentalNet(), InvoiceItemType::Rental->vatRate());
	}

	/**
	 * The courses and their laptops, after the code: billed when each course
	 * is confirmed, not now ([[13-checkout]]). `net()`, `vat()` and the
	 * discount above are all this part's; the licences have their own.
	 */
	public function bookingTotal(): string
	{
		return bcadd(bcsub($this->net(), $this->discount, 2), $this->vat(), 2);
	}

	/** The licences, before VAT. A discount code does not touch them (#3). */
	public function licenceNet(): string
	{
		return array_reduce($this->licences, fn (string $sum, BasketLicence $line) => bcadd($sum, $line->net, 2), '0.00');
	}

	public function licenceVat(): string
	{
		return array_reduce($this->licences, fn (string $sum, BasketLicence $line) => bcadd($sum, $line->vat, 2), '0.00');
	}

	/** What is invoiced, and paid by card, at checkout. */
	public function licenceTotal(): string
	{
		return bcadd($this->licenceNet(), $this->licenceVat(), 2);
	}

	/** What the customer is asked to pay, and the figure the till agrees on: both parts. */
	public function total(): string
	{
		return bcadd($this->bookingTotal(), $this->licenceTotal(), 2);
	}

	/**
	 * Refuse a total the customer has not seen ([[CompleteCheckout]]): the
	 * whole basket's, courses and licences, since that is the one shown.
	 *
	 * @throws BasketPriceChanged
	 */
	public function assertShown(string $totalShown): void
	{
		if (bccomp($totalShown, $this->total(), 2) !== 0) {
			throw new BasketPriceChanged($totalShown, $this->total());
		}
	}

	public function hasCourses(): bool
	{
		return $this->items !== [];
	}

	public function hasLicences(): bool
	{
		return $this->licences !== [];
	}

	public function isEmpty(): bool
	{
		return ! $this->hasCourses() && ! $this->hasLicences();
	}

	/** @param  callable(BasketItem): string  $value */
	private function sum(callable $value): string
	{
		return array_reduce(
			$this->items,
			fn (string $carry, BasketItem $item) => bcadd($carry, $value($item), 2),
			'0.00',
		);
	}
}
