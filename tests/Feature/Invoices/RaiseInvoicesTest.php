<?php

declare(strict_types=1);

use App\Actions\Events\SetEventState;
use App\Actions\Invoices\RaiseInvoiceForBooking;
use App\Enums\EventState;
use App\Enums\InvoiceItemType;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Event;
use App\Models\Invoice;

/**
 * The load-bearing rule of the chunk: **an invoice is raised when an event is
 * confirmed, not when a seat is booked** ([[03-invoices]]).
 *
 * A booking is a commitment; an event runs once it has the numbers, and only
 * then is there something to charge for. Measured across the 539 non-rental
 * legacy invoices with a booking: 430 dated after their booking, mean lag 27.7
 * days, longest 209. One customer booked two courses on the same day and was
 * invoiced 27 days apart, because the two confirmed at different times.
 */
beforeEach(function () {
	$this->event = Event::factory()->create([
		'date' => today()->addMonths(2),
		'course_id' => Course::factory()->create(['title' => ['de' => 'Blender Modeling'], 'number' => 7])->id,
	]);
});

it('raises no invoice while the event is only planned', function () {
	Booking::factory()->for($this->event)->create();

	expect(Invoice::count())->toBe(0);
});

it('raises one invoice per seat when the event is confirmed', function () {
	Booking::factory()->for($this->event)->count(3)->create(['course_fee' => '499.00']);

	app(SetEventState::class)->execute($this->event, EventState::Confirmed);

	expect(Invoice::count())->toBe(3)
		->and(Invoice::first()->grand_total)->toBe('499.00');
});

/**
 * 183 of 710 production bookings are cancelled. Billing them would send a bill
 * for a seat nobody holds.
 */
it('skips cancelled seats', function () {
	Booking::factory()->for($this->event)->count(2)->create();
	Booking::factory()->for($this->event)->cancelled()->create();

	app(SetEventState::class)->execute($this->event, EventState::Confirmed);

	expect(Invoice::count())->toBe(2);
});

/**
 * Confirmation is re-entrant: an event can be confirmed, gain a booking, and
 * be confirmed again. Legacy leaned on the same guard through
 * `findOrCreateFromBooking`.
 */
it('does not bill the same seat twice', function () {
	$booking = Booking::factory()->for($this->event)->create();

	app(SetEventState::class)->execute($this->event, EventState::Confirmed);
	app(RaiseInvoiceForBooking::class)->execute($booking->fresh());

	expect(Invoice::count())->toBe(1);
});

it('leaves a free event unbilled rather than billing it zero', function () {
	$event = Event::factory()->create(['free_of_charge' => true]);
	Booking::factory()->for($event)->create(['course_fee' => '0.00']);

	app(SetEventState::class)->execute($event, EventState::Confirmed);

	expect(Invoice::count())->toBe(0);
});

/**
 * The other half of that distinction, and the reason it is a distinction: a
 * course paid for with a 100 % discount code *does* get a document. 16
 * historical invoices have a grand total of 0.00 for exactly that reason.
 */
it('still issues a document when a discount code covers the whole fee', function () {
	Booking::factory()->for($this->event)->create([
		'course_fee' => '499.00',
		'discount_amount' => '499.00',
	]);

	app(SetEventState::class)->execute($this->event, EventState::Confirmed);

	$invoice = Invoice::with('items')->first();

	expect($invoice->grand_total)->toBe('0.00')
		->and($invoice->net)->toBe('499.00')
		->and($invoice->discount)->toBe('499.00')
		->and($invoice->number)->toHaveLength(6);
});

/**
 * What the line-items decision buys. Today this is two invoices, two numbers
 * and two QR bills for one booking.
 */
it('puts a laptop rental on the same invoice as its course, taxed on its own', function () {
	Booking::factory()->for($this->event)->withRental()->create([
		'course_fee' => '600.00',
		'rental_fee' => '80.00',
	]);

	app(SetEventState::class)->execute($this->event, EventState::Confirmed);

	$invoice = Invoice::with('items')->first();

	expect($invoice->items)->toHaveCount(2)
		->and($invoice->net)->toBe('680.00')
		->and($invoice->vat)->toBe('6.48')
		->and($invoice->grand_total)->toBe('686.48');

	$course = $invoice->items->firstWhere('type', InvoiceItemType::Course);
	$rental = $invoice->items->firstWhere('type', InvoiceItemType::Rental);

	expect($course->vat)->toBe('0.00')
		->and($course->description)->toContain('Blender Modeling')
		->and($rental->vat)->toBe('6.48')
		->and($rental->vat_rate)->toBe('8.10');
});

/**
 * A rental with no frozen price is a data error, and both ways of guessing are
 * wrong: 0.00 gives the laptop away, today's config rate overcharges someone
 * who was quoted last year's. The booking is named so it can be fixed.
 */
it('refuses to guess a rental price that was never frozen', function () {
	$booking = Booking::factory()->for($this->event)->withRental()->create(['rental_fee' => '0.00']);

	app(RaiseInvoiceForBooking::class)->execute($booking);
})->throws(RuntimeException::class, 'rental with no frozen price');

/**
 * One bad booking must not stop the other seats being billed, and must not
 * leave the event half-confirmed: the decision to run the course has already
 * been made and the students have already been told.
 */
it('bills the rest of the course when one seat cannot be billed', function () {
	Booking::factory()->for($this->event)->count(2)->create();
	Booking::factory()->for($this->event)->withRental()->create(['rental_fee' => '0.00']);

	app(SetEventState::class)->execute($this->event, EventState::Confirmed);

	expect(Invoice::count())->toBe(2)
		->and($this->event->fresh()->state)->toBe(EventState::Confirmed);
});

it('writes the course number and dates the way the invoice prints them', function () {
	$event = Event::factory()->create([
		'date' => '2026-03-12',
		'course_id' => Course::factory()->create(['title' => ['de' => 'Blender Modeling'], 'number' => 9])->id,
	]);
	$event->dates()->createMany([['date' => '2026-03-12'], ['date' => '2026-03-13']]);
	Booking::factory()->for($event)->create();

	app(SetEventState::class)->execute($event, EventState::Confirmed);

	$item = Invoice::with('items')->first()->items->first();

	expect($item->reference)->toBe('09-120326')
		->and($item->description)->toBe('Blender Modeling, 12.–13.03.2026');
});

/**
 * Ten days to pay, and the money in before the course runs — the rule the last
 * change on the legacy branch was reaching for, with the floor it needed.
 */
it('sets the deadline ten days before the course', function () {
	Booking::factory()->for($this->event)->create();

	app(SetEventState::class)->execute($this->event, EventState::Confirmed);

	expect(Invoice::first()->due_at->toDateString())
		->toBe(today()->addMonths(2)->subDays(10)->toDateString());
});

it('never sets a deadline in the past for a course confirmed at the last minute', function () {
	$event = Event::factory()->create(['date' => today()->addDays(3)]);
	Booking::factory()->for($event)->create();

	app(SetEventState::class)->execute($event, EventState::Confirmed);

	expect(Invoice::first()->due_at->toDateString())->toBe(today()->addDays(10)->toDateString());
});

it('does not re-bill an event that was already confirmed', function () {
	Booking::factory()->for($this->event)->create();

	app(SetEventState::class)->execute($this->event, EventState::Confirmed);
	app(SetEventState::class)->execute($this->event->fresh(), EventState::Confirmed);

	expect(Invoice::count())->toBe(1);
});

it('raises nothing when an event is closed or cancelled', function () {
	Booking::factory()->for($this->event)->create();

	app(SetEventState::class)->execute($this->event, EventState::Closed);
	expect(Invoice::count())->toBe(0);

	app(SetEventState::class)->execute($this->event->fresh(), EventState::Cancelled);
	expect(Invoice::count())->toBe(0);
});

/**
 * Legacy clamped nothing: booking 000512 spent a fixed CHF 648 code on a CHF
 * 499 course and produced invoice 000419 at **−149.00, still OPEN** — a course
 * that owes the customer money. The only such booking of 710.
 *
 * A line takes at most what it is worth, which is also how chunk 06's
 * order-level draw-down behaves when the checkout's discount is bigger than the
 * first invoice raised from it ([[06-bookings]]).
 */
it('lands at zero rather than printing a negative invoice', function () {
	Booking::factory()->for($this->event)->create([
		'course_fee' => '499.00',
		'discount_amount' => '648.00',
	]);

	app(SetEventState::class)->execute($this->event, EventState::Confirmed);

	$invoice = Invoice::with('items')->first();

	expect($invoice->grand_total)->toBe('0.00')
		->and($invoice->discount)->toBe('499.00')
		->and($invoice->items->first()->total)->toBe('0.00');
});
