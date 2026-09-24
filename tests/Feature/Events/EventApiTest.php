<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Event;

/*
 * The public read side. Writing a course date is the dashboard's, tested in
 * `tests/Feature/Admin/EventFormTest.php`.
 */

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
