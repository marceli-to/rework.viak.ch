<?php

declare(strict_types=1);

use App\Enums\EventState;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;

it('derives the event date from the earliest of its dates', function () {
	$course = Course::factory()->create();

	$response = $this->actingAs(User::factory()->admin()->create())
		->postJson('/api/events', [
			'course_uuid' => $course->uuid,
			'dates' => [
				['date' => '2027-03-28', 'time_start' => '09:00', 'time_end' => '17:00'],
				['date' => '2027-03-27', 'time_start' => '09:00', 'time_end' => '17:00'],
			],
			'min_participants' => 2,
			'max_participants' => 8,
		])
		->assertCreated();

	expect($response->json('data.date'))->toBe('2027-03-27')
		->and($response->json('data.dates.0.date'))->toBe('2027-03-27')
		->and($response->json('data.state'))->toBe('planned');
});

it('rewrites the event date when the dates are replaced', function () {
	$event = Event::factory()->create(['date' => '2027-01-10']);
	$event->dates()->create(['date' => '2027-01-10']);

	$this->actingAs(User::factory()->admin()->create())
		->putJson("/api/events/{$event->uuid}", [
			'course_uuid' => $event->course->uuid,
			'dates' => [['date' => '2027-02-20']],
			'min_participants' => 2,
			'max_participants' => 8,
		])
		->assertOk()
		->assertJsonPath('data.date', '2027-02-20');
});

it('rejects a maximum below the minimum', function () {
	$course = Course::factory()->create();

	$this->actingAs(User::factory()->admin()->create())
		->postJson('/api/events', [
			'course_uuid' => $course->uuid,
			'dates' => [['date' => '2027-05-05']],
			'min_participants' => 10,
			'max_participants' => 4,
		])
		->assertJsonValidationErrors('max_participants');
});

it('rejects registration staying open past the first course date', function () {
	$course = Course::factory()->create();

	$this->actingAs(User::factory()->admin()->create())
		->postJson('/api/events', [
			'course_uuid' => $course->uuid,
			'dates' => [['date' => '2027-05-05']],
			'registration_until' => '2027-05-06',
			'min_participants' => 2,
			'max_participants' => 8,
		])
		->assertJsonValidationErrors('registration_until');
});

it('records a timestamp when an event is confirmed', function () {
	$event = Event::factory()->create();

	$response = $this->actingAs(User::factory()->admin()->create())
		->patchJson("/api/events/{$event->uuid}/state", ['state' => 'confirmed'])
		->assertOk();

	expect($response->json('data.state'))->toBe('confirmed')
		->and($response->json('data.confirmed_at'))->not->toBeNull()
		->and($response->json('data.accepts_bookings'))->toBeTrue();
});

it('stops accepting bookings once closed', function () {
	$event = Event::factory()->create();

	$this->actingAs(User::factory()->admin()->create())
		->patchJson("/api/events/{$event->uuid}/state", ['state' => 'closed'])
		->assertOk()
		->assertJsonPath('data.accepts_bookings', false);
});

it('refuses to reinstate a cancelled event', function () {
	$event = Event::factory()->create(['state' => EventState::Cancelled, 'cancelled_at' => now()]);

	$this->actingAs(User::factory()->admin()->create())
		->patchJson("/api/events/{$event->uuid}/state", ['state' => 'confirmed'])
		->assertStatus(500);

	expect($event->refresh()->state)->toBe(EventState::Cancelled);
});

it('does not let an expert change event state', function () {
	$event = Event::factory()->create();

	$this->actingAs(User::factory()->expert()->create())
		->patchJson("/api/events/{$event->uuid}/state", ['state' => 'confirmed'])
		->assertForbidden();
});

it('counts an event happening today as upcoming, not past', function () {
	$today = Event::factory()->create(['date' => today()->toDateString()]);
	Event::factory()->past()->create();

	$response = $this->getJson('/api/events')->assertOk();

	expect($response->json('data'))->toHaveCount(1)
		->and($response->json('data.0.uuid'))->toBe($today->uuid);
});

it('falls back to the course fee when the event sets none', function () {
	$course = Course::factory()->create(['fee' => 890]);
	$event = Event::factory()->create(['course_id' => $course->id, 'fee' => null]);

	$this->getJson("/api/events/{$event->uuid}")
		->assertOk()
		->assertJsonPath('data.fee', '890.00');
});

it('prefers the event fee over the course fee', function () {
	$course = Course::factory()->create(['fee' => 890]);
	$event = Event::factory()->create(['course_id' => $course->id, 'fee' => 450]);

	$this->getJson("/api/events/{$event->uuid}")
		->assertOk()
		->assertJsonPath('data.fee', '450.00');
});

it('charges nothing for a free event regardless of either fee', function () {
	$course = Course::factory()->create(['fee' => 890]);
	$event = Event::factory()->create(['course_id' => $course->id, 'fee' => 450, 'free_of_charge' => true]);

	$this->getJson("/api/events/{$event->uuid}")
		->assertOk()
		->assertJsonPath('data.fee', '0.00');
});

it('rejects an expert uuid belonging to a student', function () {
	$course = Course::factory()->create();
	$student = User::factory()->create();

	$response = $this->actingAs(User::factory()->admin()->create())
		->postJson('/api/events', [
			'course_uuid' => $course->uuid,
			'dates' => [['date' => '2027-06-01']],
			'min_participants' => 2,
			'max_participants' => 8,
			'expert_uuids' => [$student->uuid],
		])
		->assertCreated();

	expect($response->json('data.experts'))->toBeEmpty();
});
