<?php

declare(strict_types=1);

use App\Actions\Invoices\CancelInvoice;
use App\Actions\Invoices\RaiseInvoiceForBooking;
use App\Enums\CancellationReason;
use App\Models\Booking;
use App\Models\Checkout;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;

/**
 * The draw-down ([[06-bookings]]).
 *
 * A code discounts the order, but invoices are raised per confirmed event, weeks
 * apart. Rather than splitting the discount at checkout — which strands money on
 * a course that never runs — each invoice consumes what is left, capped at its
 * own course fee.
 */
beforeEach(function () {
	$this->raise = app(RaiseInvoiceForBooking::class);
	$this->user = User::factory()->create();
	$this->checkout = Checkout::factory()->for($this->user)->discounting('50.00')->create();
});

function seatOn(Checkout $checkout, string $fee): Booking
{
	$event = Event::factory()->for(Course::factory()->create(['fee' => $fee]))->create();

	return Booking::factory()
		->for($event)
		->for($checkout->user)
		->for($checkout)
		->create(['course_fee' => $fee]);
}

/**
 * The worked example from the chunk doc, and the basket that was mispriced in
 * production: 949.00 and 499.00 with a fixed CHF 50 code. The customer was
 * promised 1398.00 and must pay 1398.00 across the two invoices.
 */
it('spends the whole discount on the first invoice raised', function () {
	$first = seatOn($this->checkout, '949.00');
	$second = seatOn($this->checkout, '499.00');

	$a = $this->raise->execute($first);
	$b = $this->raise->execute($second);

	expect($a->grand_total)->toBe('899.00')
		->and($b->grand_total)->toBe('499.00')
		->and(bcadd($a->grand_total, $b->grand_total, 2))->toBe('1398.00');
});

/**
 * The property a proportional split does not have: if the second course never
 * runs, the customer still receives the full CHF 50 they were promised rather
 * than the 32.77 their share would have been.
 */
it('leaves nothing stranded when the other course never runs', function () {
	$first = seatOn($this->checkout, '949.00');
	seatOn($this->checkout, '499.00');

	expect($this->raise->execute($first)->discount)->toBe('50.00');
});

/**
 * Larger than the course it lands on: the invoice goes to 0.00 and the unusable
 * remainder stays behind in the checkout. Legacy printed −149.00 on a document.
 */
it('caps at the course fee and keeps the remainder for the next invoice', function () {
	$checkout = Checkout::factory()->for($this->user)->discounting('648.00')->create();
	$small = seatOn($checkout, '499.00');
	$large = seatOn($checkout, '949.00');

	$a = $this->raise->execute($small);
	$b = $this->raise->execute($large);

	expect($a->grand_total)->toBe('0.00')
		->and($a->discount)->toBe('499.00')
		->and($b->discount)->toBe('149.00')
		->and($b->grand_total)->toBe('800.00');
});

it('never produces an invoice a customer is owed money on', function () {
	$checkout = Checkout::factory()->for($this->user)->discounting('648.00')->create();

	$invoice = $this->raise->execute(seatOn($checkout, '499.00'));

	expect(bccomp($invoice->grand_total, '0.00', 2))->toBeGreaterThanOrEqual(0);
});

/**
 * Nothing is denormalised, so a withdrawn document gives its share back. The
 * customer was promised that money and a cancelled invoice did not deliver it.
 */
it('releases the discount again when the invoice is cancelled', function () {
	$first = seatOn($this->checkout, '949.00');
	$second = seatOn($this->checkout, '499.00');

	$a = $this->raise->execute($first);
	expect($this->checkout->remainingDiscount())->toBe('0.00');

	app(CancelInvoice::class)->execute($a, CancellationReason::BookingCancelled);

	expect($this->checkout->remainingDiscount())->toBe('50.00')
		->and($this->raise->execute($second)->discount)->toBe('50.00');
});

/** The 710 ported bookings have no checkout and keep reading their frozen copy. */
it('falls back to the amount frozen on a booking with no checkout', function () {
	$event = Event::factory()->for(Course::factory()->create(['fee' => '499.00']))->create();
	$booking = Booking::factory()->for($event)->for($this->user)->create([
		'course_fee' => '499.00',
		'discount_amount' => '49.90',
	]);

	expect($this->raise->execute($booking)->discount)->toBe('49.90');
});

it('stops drawing down once the discount is spent', function () {
	$a = seatOn($this->checkout, '949.00');
	$b = seatOn($this->checkout, '499.00');
	$c = seatOn($this->checkout, '549.00');

	$this->raise->execute($a);
	$this->raise->execute($b);

	expect($this->raise->execute($c)->discount)->toBe('0.00')
		->and($this->checkout->remainingDiscount())->toBe('0.00');
});
