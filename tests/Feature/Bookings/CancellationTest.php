<?php

declare(strict_types=1);

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Bookings\SetRental;
use App\Actions\Events\SetEventState;
use App\Actions\Invoices\RaiseInvoiceForBooking;
use App\Enums\BookingCancellationReason;
use App\Enums\CancellationReason;
use App\Enums\EventState;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;

beforeEach(function () {
	$this->cancel = app(CancelBooking::class);
	$this->raise = app(RaiseInvoiceForBooking::class);
	$this->user = User::factory()->create();
});

function seatDaysOut(int $days, string $fee = '600.00', array $eventAttributes = []): Booking
{
	$event = Event::factory()
		->for(Course::factory()->create(['fee' => $fee]))
		->create([...$eventAttributes, 'date' => now()->addDays($days)->toDateString()]);

	return Booking::factory()->for($event)->create(['course_fee' => $fee]);
}

it('charges the whole fee when a student leaves inside 11 days', function () {
	$booking = seatDaysOut(5);

	$penalty = $this->cancel->execute($booking, BookingCancellationReason::Student);

	expect($penalty)->not->toBeNull()
		->and($penalty->grand_total)->toBe('600.00')
		->and($penalty->items->first()->description)->toContain('Annullationskosten (100 %)');
});

it('charges half between 11 and 19 days', function () {
	$penalty = $this->cancel->execute(seatDaysOut(15), BookingCancellationReason::Student);

	expect($penalty->grand_total)->toBe('300.00')
		->and($penalty->items->first()->description)->toContain('Annullationskosten (50 %)');
});

it('charges nothing more than 20 days out', function () {
	expect($this->cancel->execute(seatDaysOut(30), BookingCancellationReason::Student))->toBeNull();
});

/**
 * The single most expensive thing this chunk writes down. 115 of the 143
 * bookings on VIAK-cancelled courses fall inside the 0–10 day window, because
 * VIAK decides late whether a course runs. Legacy avoided invoicing them only
 * because `EventCancelledHandler` and `Booking::cancel()` never called each
 * other.
 */
it('charges nothing when VIAK calls the course off, however late', function () {
	$penalty = $this->cancel->execute(seatDaysOut(2), BookingCancellationReason::EventCancelled);

	expect($penalty)->toBeNull();
});

it('records who cancelled', function () {
	$booking = seatDaysOut(30);

	$this->cancel->execute($booking, BookingCancellationReason::Administrator);

	expect($booking->refresh()->cancellation_reason)
		->toBe(BookingCancellationReason::Administrator);
});

it('cancels every seat when the event is called off, and charges no one', function () {
	$event = Event::factory()->for(Course::factory()->create(['fee' => '600.00']))
		->create(['date' => now()->addDays(3)->toDateString(), 'state' => EventState::Confirmed]);
	Booking::factory()->for($event)->count(3)->create(['course_fee' => '600.00']);

	app(SetEventState::class)->execute($event, EventState::Cancelled);

	expect($event->bookings()->active()->count())->toBe(0)
		->and($event->bookings->every(
			fn (Booking $b) => $b->refresh()->cancellation_reason === BookingCancellationReason::EventCancelled
		))->toBeTrue();
});

it('does nothing the second time a booking is cancelled', function () {
	$booking = seatDaysOut(5);

	$first = $this->cancel->execute($booking, BookingCancellationReason::Student);
	$second = $this->cancel->execute($booking->refresh(), BookingCancellationReason::Student);

	expect($first)->not->toBeNull()->and($second)->toBeNull();
});

/**
 * Ten of the fourteen real penalties were raised fresh, because the course had
 * not been invoiced yet when the student dropped out.
 */
it('raises a fresh penalty when the seat was never invoiced', function () {
	$penalty = $this->cancel->execute(seatDaysOut(5), BookingCancellationReason::Student);

	expect($penalty->replaces)->toBeNull();
});

/** The other four: the original is withdrawn and the penalty supersedes it. */
it('replaces an unpaid invoice and links the two', function () {
	$booking = seatDaysOut(5);
	$original = $this->raise->execute($booking);

	$penalty = $this->cancel->execute($booking, BookingCancellationReason::Student);

	expect($original->refresh()->status)->toBe(InvoiceStatus::Cancelled)
		->and($original->cancellation_reason)->toBe(CancellationReason::Replaced)
		->and($original->replaced_by_invoice_id)->toBe($penalty->id);
});

/**
 * All four already-paid late cancellations in production sat in the 100 % window,
 * where the fee already paid *was* the penalty. Billing again would charge twice.
 */
it('leaves a paid invoice alone when it already covers the penalty', function () {
	$booking = seatDaysOut(5);
	$invoice = $this->raise->execute($booking);
	$invoice->forceFill(['status' => InvoiceStatus::Paid, 'paid_at' => now()])->save();

	$penalty = $this->cancel->execute($booking, BookingCancellationReason::Student);

	expect($penalty)->toBeNull()
		->and($invoice->refresh()->status)->toBe(InvoiceStatus::Paid);
});

/**
 * Free cancellation on a seat that was already invoiced: nothing is owed, so
 * nothing stays demanded. 8 of the 14 legacy invoice cancellations are this.
 */
it('withdraws an unpaid invoice when nothing is owed', function () {
	$booking = seatDaysOut(30);
	$invoice = $this->raise->execute($booking);

	$this->cancel->execute($booking, BookingCancellationReason::Student);

	expect($invoice->refresh()->status)->toBe(InvoiceStatus::Cancelled)
		->and($invoice->cancellation_reason)->toBe(CancellationReason::BookingCancelled);
});

it('does not withdraw money that has already arrived', function () {
	$booking = seatDaysOut(30);
	$invoice = $this->raise->execute($booking);
	$invoice->forceFill(['status' => InvoiceStatus::Paid, 'paid_at' => now()])->save();

	$this->cancel->execute($booking, BookingCancellationReason::Student);

	expect($invoice->refresh()->status)->toBe(InvoiceStatus::Paid);
});

/**
 * Legacy's admin path restored a cancelled or soft-deleted booking — keeping its
 * original number and a `course_fee` frozen whenever it was first sold, and
 * leaving the penalty invoice from the cancellation standing.
 */
it('does not resurrect a cancelled booking when an admin rebooks', function () {
	$event = Event::factory()->for(Course::factory()->create(['fee' => '700.00']))->create();
	$old = Booking::factory()->for($event)->for($this->user)
		->cancelled()->create(['number' => '000100', 'course_fee' => '499.00']);

	$new = app(CreateBookingForUser::class)->execute($event, $this->user);

	expect($new->id)->not->toBe($old->id)
		->and($new->number)->not->toBe('000100')
		->and($new->course_fee)->toBe('700.00')
		->and($old->refresh()->isCancelled())->toBeTrue();
});

it('lets the laptop be dropped before the invoice, and not after', function () {
	$booking = seatDaysOut(30, '600.00', ['rentals_available' => 2]);
	app(SetRental::class)->execute($booking, true);

	expect($booking->refresh()->rental_fee)->toBe('80.00');

	$this->raise->execute($booking);

	expect(fn () => app(SetRental::class)->execute($booking->refresh(), false))
		->toThrow(RuntimeException::class);
});
