<?php

declare(strict_types=1);

use App\Actions\Bookings\CompleteCheckout;
use App\Actions\Bookings\PriceBasket;
use App\Enums\EventState;
use App\Exceptions\BasketPriceChanged;
use App\Exceptions\DiscountCodeNotRedeemable;
use App\Exceptions\SeatNotAvailable;
use App\Models\Booking;
use App\Models\Course;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\User;

beforeEach(function () {
	$this->price = app(PriceBasket::class);
	$this->checkout = app(CompleteCheckout::class);
	$this->user = User::factory()->create();
});

function eventCosting(string $fee, array $attributes = []): Event
{
	return Event::factory()->for(Course::factory()->create(['fee' => $fee]))->create($attributes);
}

/**
 * The basket that cost VIAK money. User 123, 2023-12-23, code VIAK-2GDV-2HUE,
 * fixed CHF 50 across a 949.00 and a 499.00 course: the screen showed −50.00 and
 * `Booking::create()` stored −50.00 **per booking**. The customer agreed to
 * 1398.00 and was billed 1348.00.
 */
it('takes a fixed code off the order once, not off each course', function () {
	$code = DiscountCode::factory()->create(['amount' => '50.00']);

	$basket = $this->price->execute([
		['event' => eventCosting('949.00')],
		['event' => eventCosting('499.00')],
	], $code->code);

	expect($basket->courseNet())->toBe('1448.00')
		->and($basket->discount)->toBe('50.00')
		->and($basket->total())->toBe('1398.00');
});

/**
 * Why the bug stayed invisible for three years: a rate across the total is the
 * same number as the rate per course, summed.
 */
it('gives a percentage code the same answer either way', function () {
	$code = DiscountCode::factory()->percent('10.00')->create();

	$basket = $this->price->execute([
		['event' => eventCosting('949.00')],
		['event' => eventCosting('499.00')],
	], $code->code);

	expect($basket->discount)->toBe('144.80')
		->and($basket->total())->toBe('1303.20');
});

/**
 * Booking 000512: a fixed CHF 648 code against a CHF 499 course, which became
 * invoice 000419 at −149.00, still OPEN in the live books.
 */
it('never discounts more than the courses are worth', function () {
	$code = DiscountCode::factory()->create(['amount' => '648.00']);

	$basket = $this->price->execute([['event' => eventCosting('499.00')]], $code->code);

	expect($basket->discount)->toBe('499.00')
		->and($basket->total())->toBe('0.00');
});

/**
 * `Discount::apply()` returned FALSE, `Booking::create()` wrote it into
 * `discount_amount` as 0, and the customer paid full price without being told.
 */
it('refuses an expired code instead of silently charging full price', function () {
	$code = DiscountCode::factory()->create(['valid_to' => now()->subDay()]);

	$this->price->execute([['event' => eventCosting('499.00')]], $code->code);
})->throws(DiscountCodeNotRedeemable::class);

it('refuses a code that has reached its usage limit', function () {
	$code = DiscountCode::factory()->create(['usage_limit' => 1]);
	$this->price->execute([['event' => eventCosting('499.00')]], $code->code);

	$this->checkout->execute(
		$this->user,
		$this->price->execute([['event' => eventCosting('499.00')]], $code->code),
	);

	$this->price->execute([['event' => eventCosting('499.00')]], $code->code);
})->throws(DiscountCodeNotRedeemable::class);

/** A code with only one date was valid forever in legacy. No row had one — luck. */
it('honours a validity window that is open at one end', function () {
	$code = DiscountCode::factory()->create(['valid_to' => now()->subDay(), 'valid_from' => null]);

	expect($code->isRedeemableOn(today()))->toBeFalse();
});

it('charges VAT on the laptop but never on the course', function () {
	$event = eventCosting('499.00', ['rentals_available' => 2]);

	$basket = $this->price->execute([['event' => $event, 'rental' => true]]);

	expect($basket->courseNet())->toBe('499.00')
		->and($basket->rentalNet())->toBe('80.00')
		->and($basket->vat())->toBe('6.48')
		->and($basket->total())->toBe('585.48');
});

it('will not sell a laptop for a course that has none', function () {
	$event = eventCosting('499.00', ['rentals_available' => 0]);

	$basket = $this->price->execute([['event' => $event, 'rental' => true]]);

	expect($basket->items[0]->rental)->toBeFalse()
		->and($basket->rentalNet())->toBe('0.00');
});

it('writes one checkout and a booking per course, freezing both prices', function () {
	$event = eventCosting('499.00', ['rentals_available' => 2]);
	$basket = $this->price->execute([['event' => $event, 'rental' => true]]);

	$checkout = $this->checkout->execute($this->user, $basket);

	expect($checkout->bookings)->toHaveCount(1);

	$booking = $checkout->bookings->first();
	expect($booking->course_fee)->toBe('499.00')
		->and($booking->rental_fee)->toBe('80.00')
		->and($booking->has_rental)->toBeTrue()
		->and($booking->checkout_id)->toBe($checkout->id);
});

/**
 * A basket can sit open in a tab while the fee is edited. Legacy charged
 * whatever the price was at `Booking::create()`, higher or lower.
 */
it('refuses a checkout whose price has moved since the basket was shown', function () {
	$basket = $this->price->execute([['event' => eventCosting('499.00')]]);

	$this->checkout->execute($this->user, $basket, totalShown: '449.00');
})->throws(BasketPriceChanged::class);

it('completes when the price still matches', function () {
	$basket = $this->price->execute([['event' => eventCosting('499.00')]]);

	$checkout = $this->checkout->execute($this->user, $basket, totalShown: '499.00');

	expect($checkout->bookings)->toHaveCount(1);
});

it('refuses a seat on a course that filled up while the basket was open', function () {
	$event = eventCosting('499.00', ['max_participants' => 1]);
	Booking::factory()->for($event)->create();

	$this->checkout->execute($this->user, $this->price->execute([['event' => $event]]));
})->throws(SeatNotAvailable::class);

it('refuses a seat on a course that was called off', function () {
	$event = eventCosting('499.00', ['state' => EventState::Cancelled]);

	$this->checkout->execute($this->user, $this->price->execute([['event' => $event]]));
})->throws(SeatNotAvailable::class);

it('refuses a second seat for someone who already has one', function () {
	$event = eventCosting('499.00');
	Booking::factory()->for($event)->for($this->user)->create();

	$this->checkout->execute($this->user, $this->price->execute([['event' => $event]]));
})->throws(SeatNotAvailable::class);

/** A cancelled seat frees the place back up. 183 of 710 bookings are cancelled. */
it('lets someone rebook a course they had cancelled', function () {
	$event = eventCosting('499.00');
	Booking::factory()->for($event)->for($this->user)->cancelled()->create();

	$checkout = $this->checkout->execute($this->user, $this->price->execute([['event' => $event]]));

	expect($checkout->bookings)->toHaveCount(1);
});

it('clears a bookmark for a course once it is booked', function () {
	$event = eventCosting('499.00');
	$this->user->bookmarks()->attach($event);

	$this->checkout->execute($this->user, $this->price->execute([['event' => $event]]));

	expect($this->user->hasBookmarked($event))->toBeFalse();
});

/**
 * Legacy minted these with a full-table load and primary-key order, leaving a
 * window where two checkouts in the same second took the same number.
 */
it('mints booking numbers in sequence without reusing one', function () {
	Booking::factory()->create(['number' => '000041']);

	$checkout = $this->checkout->execute($this->user, $this->price->execute([
		['event' => eventCosting('499.00')],
		['event' => eventCosting('549.00')],
	]));

	expect($checkout->bookings->pluck('number')->sort()->values()->all())
		->toBe(['000042', '000043']);
});
