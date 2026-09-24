<?php

declare(strict_types=1);

use App\Enums\EventState;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Event;
use App\Models\Location;
use App\Models\User;

/**
 * *Kursdatum erfassen* / *bearbeiten* — `/api/admin/events` ([[07-dashboard]],
 * step 6). Took over the public API's event writes and their guarantees.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$this->expert = User::factory()->expert()->create();
	$this->location = Location::factory()->create();
});

function eventPayload(array $overrides = []): array
{
	return [
		'registration_until' => '',
		'min_participants' => 2,
		'max_participants' => 8,
		'rentals_available' => 0,
		'fee' => '',
		'online' => false,
		'free_of_charge' => false,
		'publish' => true,
		'location' => test()->location->uuid,
		'dates' => [['date' => '2027-03-27', 'time_start' => '09:00', 'time_end' => '17:00']],
		'experts' => [test()->expert->uuid],
		...$overrides,
	];
}

it('keeps course dates to admins', function () {
	$course = Course::factory()->create();

	$this->actingAs($this->expert)->postJson("/api/admin/courses/{$course->uuid}/events", eventPayload())->assertForbidden();
	$this->actingAs($this->expert)->patchJson('/api/admin/events/'.Event::factory()->create()->uuid.'/state', ['state' => 'confirmed'])->assertForbidden();
});

it('has no public write path any more', function () {
	$this->actingAs($this->admin)->postJson('/api/events', [])->assertMethodNotAllowed();
});

it('derives the event date from the earliest of its days', function () {
	$course = Course::factory()->create();

	$response = $this->actingAs($this->admin)
		->postJson("/api/admin/courses/{$course->uuid}/events", eventPayload(['dates' => [
			['date' => '2027-03-28', 'time_start' => '09:00', 'time_end' => '17:00'],
			['date' => '2027-03-27', 'time_start' => '', 'time_end' => ''],
		]]))
		->assertCreated();

	$event = Event::where('uuid', $response->json('data.uuid'))->first();

	expect($event->date->toDateString())->toBe('2027-03-27')
		->and($event->state)->toBe(EventState::Planned)
		->and($event->course_id)->toBe($course->id)
		->and($response->json('data.dates.0'))->toBe(['date' => '2027-03-27', 'time_start' => '', 'time_end' => ''])
		->and($response->json('data.dates.1.time_start'))->toBe('09:00');
});

it('sends back exactly what it loads', function () {
	$course = Course::factory()->create();
	$uuid = $this->actingAs($this->admin)->postJson("/api/admin/courses/{$course->uuid}/events", eventPayload(['fee' => '450']))->json('data.uuid');

	$form = collect($this->getJson("/api/admin/events/{$uuid}")->assertOk()->json('data'))->only(array_keys(eventPayload()))->all();

	expect(collect($this->putJson("/api/admin/events/{$uuid}", $form)->assertOk()->json('data'))->only(array_keys($form))->all())->toBe($form);
});

it('rewrites the event date when the days change', function () {
	$event = Event::factory()->create(['date' => '2027-01-10']);
	$event->dates()->create(['date' => '2027-01-10']);

	$this->actingAs($this->admin)
		->putJson("/api/admin/events/{$event->uuid}", eventPayload(['dates' => [['date' => '2027-02-20']]]))
		->assertOk();

	expect($event->refresh()->date->toDateString())->toBe('2027-02-20')
		->and($event->dates()->count())->toBe(1);
});

it('refuses a maximum below the minimum, in German', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/courses/'.Course::factory()->create()->uuid.'/events', eventPayload(['min_participants' => 10, 'max_participants' => 4]))
		->assertJsonValidationErrors(['max_participants' => 'Darf nicht kleiner sein als min. Teilnehmer.']);

	// Not held back by a mistake elsewhere on the form.
	$this->postJson('/api/admin/courses/'.Course::factory()->create()->uuid.'/events', eventPayload(['min_participants' => 10, 'max_participants' => 4, 'dates' => [['date' => '25.09.25']]]))
		->assertJsonValidationErrors(['max_participants', 'dates.0.date']);
});

it('refuses registration staying open past the first day', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/courses/'.Course::factory()->create()->uuid.'/events', eventPayload(['registration_until' => '2027-03-28']))
		->assertJsonValidationErrors(['registration_until' => 'Darf nicht nach dem ersten Kurstag liegen.']);
});

it('refuses a day without a four-digit year, and asks for one day and one expert', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/courses/'.Course::factory()->create()->uuid.'/events', eventPayload(['dates' => [['date' => '25.09.25']]]))
		->assertJsonValidationErrors(['dates.0.date' => 'Bitte als TT.MM.JJJJ erfassen, mit vierstelligem Jahr.']);

	$this->postJson('/api/admin/courses/'.Course::factory()->create()->uuid.'/events', eventPayload(['dates' => [], 'experts' => []]))
		->assertJsonValidationErrors([
			'dates' => 'Bitte mindestens ein Datum erfassen.',
			'experts' => 'Bitte mindestens einen Experten wählen.',
		]);
});

it('refuses a day that ends before it starts', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/courses/'.Course::factory()->create()->uuid.'/events', eventPayload(['dates' => [['date' => '2027-03-27', 'time_start' => '17:00', 'time_end' => '09:00']]]))
		->assertJsonValidationErrors(['dates.0.time_end' => 'Endet vor dem Beginn.']);
});

it('attaches experts only, not a student', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/courses/'.Course::factory()->create()->uuid.'/events', eventPayload(['experts' => [User::factory()->create()->uuid]]))
		->assertJsonValidationErrors('experts.0');

	// Named after the group, as the dashboard shows it under the boxes.
	expect($this->postJson('/api/admin/courses/'.Course::factory()->create()->uuid.'/events', eventPayload(['experts' => ['']]))->json('errors')['experts.0'][0])
		->toContain('Experten');
});

it('keeps a date with bookings, cancelled ones included', function () {
	$event = Event::factory()->create();
	Booking::factory()->cancelled()->create(['event_id' => $event->id]);

	$this->actingAs($this->admin)->getJson("/api/admin/events/{$event->uuid}")->assertJsonPath('data.bookings', 1);
	$this->deleteJson("/api/admin/events/{$event->uuid}")->assertStatus(422);

	expect($event->refresh()->trashed())->toBeFalse();
});

it('deletes a date nobody booked', function () {
	$event = Event::factory()->create();

	$this->actingAs($this->admin)->deleteJson("/api/admin/events/{$event->uuid}")->assertNoContent();

	expect($event->refresh()->trashed())->toBeTrue();
});

it('records a timestamp when an event is confirmed', function () {
	$event = Event::factory()->create();

	$this->actingAs($this->admin)->patchJson("/api/admin/events/{$event->uuid}/state", ['state' => 'confirmed'])
		->assertOk()
		->assertJsonPath('data.state', 'confirmed');

	expect($event->refresh()->confirmed_at)->not->toBeNull()
		->and($event->state->acceptsBookings())->toBeTrue();
});

it('stops accepting bookings once closed', function () {
	$event = Event::factory()->create();

	$this->actingAs($this->admin)->patchJson("/api/admin/events/{$event->uuid}/state", ['state' => 'closed'])->assertOk();

	expect($event->refresh()->state->acceptsBookings())->toBeFalse();
});

it('refuses to reinstate a cancelled event', function () {
	$event = Event::factory()->create(['state' => EventState::Cancelled, 'cancelled_at' => now()]);

	$this->actingAs($this->admin)->patchJson("/api/admin/events/{$event->uuid}/state", ['state' => 'confirmed'])->assertStatus(500);

	expect($event->refresh()->state)->toBe(EventState::Cancelled);
});
