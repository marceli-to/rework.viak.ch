<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Event;
use App\Support\CancellationPenalty;
use Illuminate\Support\Carbon;

/**
 * The boundaries are worth real money and the failure is silent, so every one
 * of them is pinned here ([[06-bookings]]).
 */
beforeEach(function () {
	$this->penalty = new CancellationPenalty;
	$this->on = Carbon::parse('2026-03-01');
});

function bookingOn(string $eventDate, string $fee = '499.00', string $discount = '0.00'): Booking
{
	$event = Event::factory()->create(['date' => $eventDate]);

	return Booking::factory()->for($event)->create([
		'course_fee' => $fee,
		'discount_amount' => $discount,
	]);
}

/**
 * The regression this class exists for. Legacy's `diffInDays()` was absolute
 * under Carbon 2.73; the rework runs Carbon 3.13 where it is signed. Ported
 * verbatim, `-33 < 11` is true and every cancellation of a *future* event
 * charges 100 %.
 */
it('does not charge a penalty for a course that is months away', function () {
	$booking = bookingOn('2026-04-03'); // 33 days out

	expect($this->penalty->daysUntil($booking, $this->on))->toBe(33)
		->and($this->penalty->rate($booking, $this->on))->toBe('0.00')
		->and($this->penalty->applies($booking, $this->on))->toBeFalse();
});

it('charges nothing from 20 days out', function (string $date, int $days) {
	$booking = bookingOn($date);

	expect($this->penalty->daysUntil($booking, $this->on))->toBe($days)
		->and($this->penalty->rate($booking, $this->on))->toBe('0.00');
})->with([
	'the boundary itself' => ['2026-03-21', 20],
	'a day past it' => ['2026-03-22', 21],
]);

/**
 * Production rates, read off the ten penalty invoices that were actually
 * raised: 50 % at 17, 17, 13, 12 and 12 days.
 */
it('charges half between 11 and 19 days', function (string $date, int $days) {
	$booking = bookingOn($date, '600.00');

	expect($this->penalty->daysUntil($booking, $this->on))->toBe($days)
		->and($this->penalty->rate($booking, $this->on))->toBe('0.50')
		->and($this->penalty->amount($booking, $this->on))->toBe('300.00');
})->with([
	'the upper boundary' => ['2026-03-20', 19],
	'a real case at 17' => ['2026-03-18', 17],
	'a real case at 12' => ['2026-03-13', 12],
	'the lower boundary' => ['2026-03-12', 11],
]);

/** 100 % at 8, 7, 5, 1 and 0 days, again from the invoices that were raised. */
it('charges the whole fee inside 11 days', function (string $date, int $days) {
	$booking = bookingOn($date, '600.00');

	expect($this->penalty->daysUntil($booking, $this->on))->toBe($days)
		->and($this->penalty->rate($booking, $this->on))->toBe('1.00')
		->and($this->penalty->amount($booking, $this->on))->toBe('600.00');
})->with([
	'the boundary' => ['2026-03-11', 10],
	'a real case at 8' => ['2026-03-09', 8],
	'a real case at 1' => ['2026-03-02', 1],
	'the day itself' => ['2026-03-01', 0],
]);

/** One of the 40 student cancellations happened after the course had run. */
it('charges the whole fee after the course has run', function () {
	$booking = bookingOn('2026-02-28');

	expect($this->penalty->daysUntil($booking, $this->on))->toBe(-1)
		->and($this->penalty->rate($booking, $this->on))->toBe('1.00');
});

/**
 * Legacy's second test: the fee must exceed the discount. One of the nine late
 * cancellations was fully discounted and so exempt — a student who paid nothing
 * owes nothing.
 */
it('charges nothing when the discount covered the whole fee', function () {
	$booking = bookingOn('2026-03-02', '499.00', '499.00');

	expect($this->penalty->rate($booking, $this->on))->toBe('1.00')
		->and($this->penalty->amount($booking, $this->on))->toBe('0.00')
		->and($this->penalty->applies($booking, $this->on))->toBeFalse();
});

/** The penalty follows what was agreed, not the list price. */
it('charges the discounted fee, not the full one', function () {
	$booking = bookingOn('2026-03-02', '499.00', '99.00');

	expect($this->penalty->amount($booking, $this->on))->toBe('400.00');
});
