<?php

declare(strict_types=1);

use App\Actions\Bookings\CompleteCheckout;
use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Bookings\PriceBasket;
use App\Actions\Bookings\SetRental;
use App\Exceptions\SeatNotAvailable;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;

/**
 * Laptops are counted, not switched on ([[06-bookings]]).
 *
 * Legacy's `rentals_available` is how many machines the room has — 1, 2 or 3
 * on the dates that have any — and a laptop is offered only while fewer are
 * rented. The port had flattened it to a boolean, so two laptops could be
 * rented five times (`Todo.md`, *Rental capacity*, fixed 2026-09-24).
 */
function roomWith(int $laptops, int $rented = 0): Event
{
	$event = Event::factory()->create(['rentals_available' => $laptops, 'max_participants' => 20]);
	Booking::factory()->for($event)->count($rented)->create(['has_rental' => true, 'rental_fee' => '80.00']);

	return $event;
}

it('counts what is left from active rentals only', function () {
	$event = roomWith(3, rented: 1);
	Booking::factory()->for($event)->create(['has_rental' => true, 'cancelled_at' => now()]);
	Booking::factory()->for($event)->create(['has_rental' => false]);

	expect($event->rentalsLeft())->toBe(2)
		->and(roomWith(0)->offersRental())->toBeFalse();
});

it('sells the last laptop, and refuses the one after it at checkout', function () {
	$event = roomWith(2, rented: 1);
	$price = app(PriceBasket::class);
	$checkout = app(CompleteCheckout::class);

	$checkout->execute(User::factory()->create(), $price->execute([['event' => $event, 'rental' => true]]));

	expect($event->rentalsLeft())->toBe(0);

	$late = $price->execute([['event' => $event->fresh(), 'rental' => true]]);

	expect(fn () => $checkout->execute(User::factory()->create(), $late))
		->toThrow(SeatNotAvailable::class, 'keine Mietcomputer mehr');
});

it('still sells the seat without a laptop when the laptops are gone', function () {
	$event = roomWith(1, rented: 1);

	$checkout = app(CompleteCheckout::class)->execute(
		User::factory()->create(),
		app(PriceBasket::class)->execute([['event' => $event, 'rental' => false]]),
	);

	expect($checkout->bookings->first()->has_rental)->toBeFalse();
});

it('refuses to add a laptop to a booking once none is left, but always lets one go', function () {
	$event = roomWith(1, rented: 1);
	$holder = $event->bookings()->first();
	$other = Booking::factory()->for($event)->create();

	expect(fn () => app(SetRental::class)->execute($other, true))->toThrow(SeatNotAvailable::class);

	app(SetRental::class)->execute($holder, false);

	expect(app(SetRental::class)->execute($other, true)->has_rental)->toBeTrue();
});

it('tells the portal why, in words a customer can read', function () {
	$event = roomWith(1, rented: 1);
	$student = User::factory()->student()->create(['email_verified_at' => now()]);
	$booking = Booking::factory()->for($event)->for($student)->create();

	$this->actingAs($student)
		->patchJson("/api/bookings/{$booking->uuid}/rental", ['rental' => true])
		->assertUnprocessable()
		->assertJsonPath('message', "Für «{$event->course->title}» sind keine Mietcomputer mehr verfügbar.");
});

it('refuses an admin booking with a laptop that is not there', function () {
	$event = roomWith(1, rented: 1);

	expect(fn () => app(CreateBookingForUser::class)->execute($event, User::factory()->create(), rental: true))
		->toThrow(SeatNotAvailable::class);

	expect(app(CreateBookingForUser::class)->execute($event, User::factory()->create())->has_rental)->toBeFalse();
});

/**
 * These reach the customer as they are, in a toast. Three were English until
 * 2026-09-24, and none said which course of a basket was meant.
 */
it('tells the customer in German, and names the course', function () {
	$event = roomWith(0);
	$event->course->update(['title' => ['de' => 'Blender Einführungskurs']]);
	$event->refresh();

	expect(SeatNotAvailable::full($event)->getMessage())->toBe('«Blender Einführungskurs»: Kurs ist ausgebucht.')
		->and(SeatNotAvailable::closed($event)->getMessage())->toBe('«Blender Einführungskurs» kann nicht mehr gebucht werden.')
		->and(SeatNotAvailable::alreadyBooked($event)->getMessage())->toBe('Du hast bereits eine Buchung für «Blender Einführungskurs».')
		->and(SeatNotAvailable::noRentalLeft($event)->getMessage())->toBe('Für «Blender Einführungskurs» sind keine Mietcomputer mehr verfügbar.');
});
