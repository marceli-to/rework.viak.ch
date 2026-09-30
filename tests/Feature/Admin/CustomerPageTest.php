<?php

declare(strict_types=1);

use App\Enums\BookingCancellationReason;
use App\Jobs\SendCancellationConfirmation;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

/**
 * The student's page on the dashboard ([[07-dashboard]], step 7), and
 * *Annullieren* with the admin's answer to the cost (#14).
 */
beforeEach(function () {
	Queue::fake([SendCancellationConfirmation::class]);
	$this->admin = User::factory()->admin()->create();
	$this->student = User::factory()->student()->create();
});

function studentSeat(User $student, int $days, string $fee = '600.00'): Booking
{
	$event = Event::factory()
		->for(Course::factory()->create(['fee' => $fee]))
		->create(['date' => now()->addDays($days)->toDateString()]);

	return Booking::factory()->for($event)->for($student)->create(['course_fee' => $fee]);
}

it('lists booked, past and cancelled seats apart, with what cancelling would cost', function () {
	$soon = studentSeat($this->student, 5);
	$later = studentSeat($this->student, 40);
	$past = studentSeat($this->student, -30);
	$gone = studentSeat($this->student, 60);
	$gone->forceFill(['cancelled_at' => now(), 'cancellation_reason' => BookingCancellationReason::Student])->save();

	$this->actingAs($this->admin)->getJson("/api/admin/customers/{$this->student->uuid}/page")
		->assertOk()
		->assertJsonPath('data.customer.email', $this->student->email)
		->assertJsonPath('data.booked.0.uuid', $soon->uuid)
		->assertJsonPath('data.booked.0.penalty', ['applies' => true, 'amount' => '600.00', 'rate' => 100])
		->assertJsonPath('data.booked.1.uuid', $later->uuid)
		->assertJsonPath('data.booked.1.penalty.applies', false)
		->assertJsonPath('data.past.0.uuid', $past->uuid)
		->assertJsonPath('data.cancelled.0.uuid', $gone->uuid)
		->assertJsonPath('data.cancelled.0.reason', 'Durch Kunde');
});

it('charges the cost when the admin says so', function () {
	$booking = studentSeat($this->student, 5);

	$this->actingAs($this->admin)->patchJson("/api/admin/bookings/{$booking->uuid}/cancel", ['charge_penalty' => true])
		->assertOk()
		->assertJsonPath('data.reason', 'administrator')
		->assertJsonPath('data.penalty.grand_total', '600.00');

	expect($booking->refresh()->cancellation_reason)->toBe(BookingCancellationReason::Administrator);
});

it('lets the cost go when the admin says so, and writes the waiver down', function () {
	$booking = studentSeat($this->student, 5);

	$this->actingAs($this->admin)->patchJson("/api/admin/bookings/{$booking->uuid}/cancel", ['charge_penalty' => false])
		->assertOk()
		->assertJsonPath('data.reason', 'administrator_waived')
		->assertJsonPath('data.penalty', null);

	expect($booking->refresh()->isCancelled())->toBeTrue()
		->and($booking->cancellation_reason)->toBe(BookingCancellationReason::AdministratorWaived)
		->and($booking->invoice())->toBeNull();
});

it('records no waiver where there was nothing to waive', function () {
	$booking = studentSeat($this->student, 40);

	$this->actingAs($this->admin)->patchJson("/api/admin/bookings/{$booking->uuid}/cancel", ['charge_penalty' => false])
		->assertOk()->assertJsonPath('data.reason', 'administrator');
});

it('wants the answer, and refuses a seat that is gone or on a course that has run', function () {
	$booking = studentSeat($this->student, 5);
	$this->actingAs($this->admin)->patchJson("/api/admin/bookings/{$booking->uuid}/cancel", [])->assertJsonValidationErrors('charge_penalty');

	$past = studentSeat($this->student, -3);
	$this->patchJson("/api/admin/bookings/{$past->uuid}/cancel", ['charge_penalty' => false])->assertStatus(422);

	$this->patchJson("/api/admin/bookings/{$booking->uuid}/cancel", ['charge_penalty' => false])->assertOk();
	$this->patchJson("/api/admin/bookings/{$booking->uuid}/cancel", ['charge_penalty' => false])->assertStatus(422);
});

it('keeps the page and the cancel to admins', function () {
	$booking = studentSeat($this->student, 5);

	$this->actingAs($this->student)->getJson("/api/admin/customers/{$this->student->uuid}/page")->assertForbidden();
	$this->patchJson("/api/admin/bookings/{$booking->uuid}/cancel", ['charge_penalty' => false])->assertForbidden();
	expect($booking->refresh()->isCancelled())->toBeFalse();
});

it('shows a seat on an event legacy deleted, and does not cancel it', function () {
	$booking = studentSeat($this->student, -40);
	$booking->event->delete();
	$gone = studentSeat($this->student, 30);
	$gone->event->course->delete();

	$this->actingAs($this->admin)->getJson("/api/admin/customers/{$this->student->uuid}/page")
		->assertOk()
		->assertJsonPath('data.past.0.uuid', $booking->uuid)
		->assertJsonPath('data.past.0.deleted', true)
		->assertJsonPath('data.booked.0.deleted', true);

	$this->patchJson("/api/admin/bookings/{$booking->uuid}/cancel", ['charge_penalty' => false])->assertStatus(422);
});
