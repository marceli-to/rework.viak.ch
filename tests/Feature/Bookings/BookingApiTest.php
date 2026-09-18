<?php

declare(strict_types=1);

use App\Enums\BookingCancellationReason;
use App\Models\Booking;
use App\Models\Course;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\User;

beforeEach(function () {
	$this->student = User::factory()->student()->create();
});

function bookableEvent(string $fee = '499.00', array $attributes = []): Event
{
	return Event::factory()
		->for(Course::factory()->create(['fee' => $fee]))
		->create($attributes);
}

it('prices a basket without ever being told a price', function () {
	$a = bookableEvent('949.00');
	$b = bookableEvent('499.00');
	$code = DiscountCode::factory()->create(['amount' => '50.00']);

	$response = $this->actingAs($this->student)
		->postJson('/api/basket/price', [
			'items' => [['event' => $a->uuid], ['event' => $b->uuid]],
			'code' => $code->code,
		])
		->assertOk();

	expect($response->json('data.course_net'))->toBe('1448.00')
		->and($response->json('data.discount'))->toBe('50.00')
		->and($response->json('data.total'))->toBe('1398.00');
});

it('tells the customer when a code will not apply', function () {
	$code = DiscountCode::factory()->create(['valid_to' => now()->subDay()]);

	$this->actingAs($this->student)
		->postJson('/api/basket/price', [
			'items' => [['event' => bookableEvent()->uuid]],
			'code' => $code->code,
		])
		->assertStatus(422)
		->assertJsonPath('errors.code.0', fn (string $m) => str_contains($m, 'not valid'));
});

it('completes a checkout and returns the bookings it made', function () {
	$event = bookableEvent('499.00');

	$response = $this->actingAs($this->student)
		->postJson('/api/checkout', [
			'items' => [['event' => $event->uuid]],
			'total_shown' => '499.00',
		])
		->assertCreated();

	expect($response->json('data.bookings'))->toHaveCount(1)
		->and($response->json('data.bookings.0.course_fee'))->toBe('499.00');
});

it('refuses a checkout whose price has moved', function () {
	$event = bookableEvent('499.00');

	$this->actingAs($this->student)
		->postJson('/api/checkout', [
			'items' => [['event' => $event->uuid]],
			'total_shown' => '449.00',
		])
		->assertStatus(422)
		->assertJsonPath('actual', '499.00');
});

it('refuses a seat on a course that is full', function () {
	$event = bookableEvent('499.00', ['max_participants' => 1]);
	Booking::factory()->for($event)->create();

	$this->actingAs($this->student)
		->postJson('/api/checkout', [
			'items' => [['event' => $event->uuid]],
			'total_shown' => '499.00',
		])
		->assertStatus(422)
		->assertJsonPath('event', $event->uuid);
});

it('will not price a basket for a guest', function () {
	$this->postJson('/api/basket/price', ['items' => [['event' => bookableEvent()->uuid]]])
		->assertUnauthorized();
});

it('lists only the caller’s own bookings', function () {
	Booking::factory()->for($this->student)->count(2)->create();
	Booking::factory()->count(3)->create();

	$this->actingAs($this->student)->getJson('/api/bookings')
		->assertOk()
		->assertJsonCount(2, 'data');
});

/**
 * Object-level ownership. Legacy checked this in nine places across the whole
 * application, and all 28 of its FormRequests authorised everything
 * ([[08-accounts]]).
 */
it('will not show one student another student’s booking', function () {
	$booking = Booking::factory()->create();

	$this->actingAs($this->student)->getJson("/api/bookings/{$booking->uuid}")
		->assertForbidden();
});

it('will not let one student cancel another student’s booking', function () {
	$booking = Booking::factory()->create();

	$this->actingAs($this->student)->patchJson("/api/bookings/{$booking->uuid}/cancel")
		->assertForbidden();
});

it('cancels a seat and reports the penalty in the same response', function () {
	$event = Event::factory()->for(Course::factory()->create(['fee' => '600.00']))
		->create(['date' => now()->addDays(5)->toDateString()]);
	$booking = Booking::factory()->for($event)->for($this->student)->create(['course_fee' => '600.00']);

	$response = $this->actingAs($this->student)
		->patchJson("/api/bookings/{$booking->uuid}/cancel")
		->assertOk();

	expect($response->json('data.is_cancelled'))->toBeTrue()
		->and($response->json('data.cancellation_reason'))->toBe(BookingCancellationReason::Student->value)
		->and($response->json('penalty.grand_total'))->toBe('600.00');
});

it('reports no penalty when the student leaves in good time', function () {
	$event = Event::factory()->for(Course::factory()->create(['fee' => '600.00']))
		->create(['date' => now()->addDays(40)->toDateString()]);
	$booking = Booking::factory()->for($event)->for($this->student)->create(['course_fee' => '600.00']);

	$this->actingAs($this->student)
		->patchJson("/api/bookings/{$booking->uuid}/cancel")
		->assertOk()
		->assertJsonPath('penalty', null);
});

/** An admin cancelling for a student is recorded as such, not passed off as theirs. */
it('records an admin cancellation as an admin cancellation', function () {
	$booking = Booking::factory()->create();

	$this->actingAs(User::factory()->admin()->create())
		->patchJson("/api/bookings/{$booking->uuid}/cancel")
		->assertOk()
		->assertJsonPath('data.cancellation_reason', BookingCancellationReason::Administrator->value);
});

it('adds and drops the laptop', function () {
	$event = bookableEvent('499.00', ['rentals_available' => true]);
	$booking = Booking::factory()->for($event)->for($this->student)->create();

	$this->actingAs($this->student)
		->patchJson("/api/bookings/{$booking->uuid}/rental", ['rental' => true])
		->assertOk()
		->assertJsonPath('data.rental_fee', '80.00');

	$this->actingAs($this->student)
		->patchJson("/api/bookings/{$booking->uuid}/rental", ['rental' => false])
		->assertOk()
		->assertJsonPath('data.has_rental', false);
});

it('saves and forgets a course', function () {
	$event = bookableEvent();

	$this->actingAs($this->student)->putJson("/api/bookmarks/{$event->uuid}")->assertNoContent();
	$this->actingAs($this->student)->getJson('/api/bookmarks')->assertJsonCount(1, 'data');

	$this->actingAs($this->student)->deleteJson("/api/bookmarks/{$event->uuid}")->assertNoContent();
	$this->actingAs($this->student)->getJson('/api/bookmarks')->assertJsonCount(0, 'data');
});
