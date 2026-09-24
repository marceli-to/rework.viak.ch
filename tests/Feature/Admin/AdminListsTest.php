<?php

declare(strict_types=1);

use App\Enums\EventState;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;

/**
 * The dashboard's *Kurse* screen: `/api/admin/courses` and its order
 * ([[07-dashboard]]).
 */
function admin(): User
{
	return User::factory()->admin()->create(['email_verified_at' => now()]);
}

it('keeps the admin endpoints to admins', function (string $method, string $path) {
	$this->json($method, $path)->assertUnauthorized();

	$this->actingAs(User::factory()->expert()->create())->json($method, $path)->assertForbidden();
})->with([
	['GET', '/api/admin/courses'],
	['POST', '/api/admin/courses/order'],
]);

it('lists every course, published or not, in catalogue order', function () {
	Course::factory()->create(['title' => ['de' => 'Zweiter'], 'order' => 2, 'publish' => false]);
	Course::factory()->create(['title' => ['de' => 'Erster'], 'order' => 1]);

	expect(array_column($this->actingAs(admin())->getJson('/api/admin/courses')->json('data'), 'title'))
		->toBe(['Erster', 'Zweiter']);
});

/**
 * Legacy's screen opens on the next date, not on 2022: upcoming only, soonest
 * first, cancelled left out — unpublished kept, because it is the admin's list.
 */
it('gives each course its upcoming dates only, soonest first', function () {
	$course = Course::factory()->create();
	Event::factory()->for($course)->create(['date' => today()->addDays(20)]);
	Event::factory()->for($course)->create(['date' => today()->addDays(5), 'publish' => false]);
	Event::factory()->for($course)->create(['date' => today()->subDay()]);
	Event::factory()->for($course)->in(EventState::Cancelled)->create(['date' => today()->addDays(9)]);

	$dates = $this->actingAs(admin())->getJson('/api/admin/courses')->json('data.0.events.*.date');

	expect($dates)->toBe([today()->addDays(5)->toDateString(), today()->addDays(20)->toDateString()]);
});

it('counts active bookings and rented laptops per date, and spells times the site’s way', function () {
	$event = Event::factory()->create(['max_participants' => 8, 'rentals_available' => true]);
	$event->dates()->create(['date' => $event->date, 'time_start' => '08:30:00', 'time_end' => '17:00:00']);
	Booking::factory()->for($event)->count(2)->create();
	Booking::factory()->for($event)->create(['has_rental' => true]);
	Booking::factory()->for($event)->create(['cancelled_at' => now()]);

	$row = $this->actingAs(admin())->getJson('/api/admin/courses')->json('data.0.events.0');

	expect($row['bookings'])->toBe(3)
		->and($row['rentals'])->toBe(1)
		->and($row['max_participants'])->toBe(8)
		->and($row['dates'][0])->toMatchArray(['time_start' => '08.30', 'time_end' => '17.00']);
});

it('saves the accordion’s order as 1…n', function () {
	[$a, $b, $c] = Course::factory()->count(3)->create(['order' => 5]);

	$this->actingAs(admin())
		->postJson('/api/admin/courses/order', ['courses' => [$c->uuid, $a->uuid, $b->uuid]])
		->assertNoContent();

	expect([$c->fresh()->order, $a->fresh()->order, $b->fresh()->order])->toBe([1, 2, 3]);
});

it('refuses an order naming a course that does not exist', function () {
	$this->actingAs(admin())
		->postJson('/api/admin/courses/order', ['courses' => ['not-a-course']])
		->assertUnprocessable();
});

it('changes a date’s state and answers with the row', function () {
	$event = Event::factory()->create();

	$this->actingAs(admin())
		->patchJson("/api/admin/events/{$event->uuid}/state", ['state' => 'confirmed'])
		->assertOk()
		->assertJsonPath('data.state', 'confirmed');
});
