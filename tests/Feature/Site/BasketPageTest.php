<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

/**
 * The basket, step 1 of 4 ([[09-public-site]]).
 *
 * **The rows are not here.** The selection lives in `localStorage`, so the
 * server renders a shell and `<template x-for>` fills it from
 * `/api/basket/price` — the one client-rendered screen on this site, and the
 * reason `00-foundation.md` kept that endpoint. What is asserted here is what
 * the server does own: who may open the page, and the flag the page paints a
 * row red on.
 */
function student(): User
{
	return User::factory()->student()->create(['email_verified_at' => now()]);
}

it('sends a guest to the login rather than showing a basket', function () {
	$this->get('/de/checkout/basket')->assertRedirect('/login');
});

/**
 * Legacy wraps every checkout step in `role:student` inside a group that is
 * already `auth:sanctum, verified` — so an admin who is not also a student gets
 * a 403. Roles are capabilities rather than a rank ([[Role]]), and a booking
 * belongs to a `user_id`.
 */
it('is a student’s page, and an admin without that role is refused', function () {
	$admin = User::factory()->admin()->create(['email_verified_at' => now()]);

	$this->actingAs($admin)->get('/de/checkout/basket')->assertForbidden();
});

it('sends an unverified student to the verification notice', function () {
	$user = User::factory()->student()->create(['email_verified_at' => null]);

	$this->actingAs($user)->get('/de/checkout/basket')->assertRedirect('/email/verify');
});

it('gives a student the shell, on legacy’s URL and behind the teal gutters', function () {
	$this->actingAs(student())
		->get('/de/checkout/basket')
		->assertOk()
		->assertSee('Mein Warenkorb')
		->assertSee('Schritt 1/4')
		->assertSee('Dein Warenkorb ist leer...')
		// `is-auth` on `<html>`, as every step of legacy's checkout has it.
		->assertSee('class="overflow-y-scroll bg-teal"', false)
		->assertSee('x-data="basketList"', false);
});

/**
 * The flag the page paints a row red on.
 *
 * [[CompleteCheckout]] refuses a duplicate outright, with this same query.
 * Learning that at the last step is a poor place to learn it, so the basket
 * says so first — which is also the only thing `PriceBasket`'s `$for` argument
 * has ever been for.
 */
it('marks a basket line the customer has already booked', function () {
	$user = student();
	$event = Event::factory()->for(Course::factory())->create();

	$this->actingAs($user)
		->postJson('/api/basket/price', ['items' => [['event' => $event->uuid]]])
		->assertOk()
		->assertJsonPath('data.items.0.booked', false);

	Booking::factory()->for($user)->for($event)->create();

	$this->actingAs($user)
		->postJson('/api/basket/price', ['items' => [['event' => $event->uuid]]])
		->assertOk()
		->assertJsonPath('data.items.0.booked', true);
});

it('does not mark a line another customer booked', function () {
	$event = Event::factory()->for(Course::factory())->create();
	Booking::factory()->for(User::factory())->for($event)->create();

	$this->actingAs(student())
		->postJson('/api/basket/price', ['items' => [['event' => $event->uuid]]])
		->assertOk()
		->assertJsonPath('data.items.0.booked', false);
});

/**
 * The row needs where it is and with whom, and nothing else on the page can ask
 * for them — so `PriceBasketRequest` loads them. `EventResource` guards both
 * with `whenLoaded`, which is why this has to be asserted rather than assumed.
 */
it('gives the basket row its place and its experts', function () {
	$event = Event::factory()->for(Course::factory())->create();
	$expert = User::factory()->create(['first_name' => 'Helge', 'last_name' => 'Maus']);
	$event->experts()->attach($expert);

	$this->actingAs(student())
		->postJson('/api/basket/price', ['items' => [['event' => $event->uuid]]])
		->assertOk()
		->assertJsonPath('data.items.0.event.experts.0.name', 'Helge Maus')
		->assertJsonStructure(['data' => ['items' => [['event' => ['location', 'dates', 'course']]]]]);
});

/**
 * The bug 251 passing booking tests could not see ([[09-public-site]]).
 *
 * Laravel's slim skeleton does not register
 * `EnsureFrontendRequestsAreStateful`, so `auth:sanctum` fell through to the
 * **token** guard on every API request and answered a signed-in browser with
 * `Unauthenticated.` — the basket, the bookings, the profile, the whole
 * dashboard. `actingAs()` sets the guard directly and never goes near the
 * middleware, which is exactly why the suite was silent about it; the first
 * screen to make a real request from a real session was this one.
 *
 * Asserted on the stack rather than through a request for that reason: a test
 * that authenticates the way the tests do would pass either way.
 */
it('runs the API through Sanctum’s stateful middleware, so a session can reach it', function () {
	expect(app('router')->getMiddlewareGroups()['api'] ?? [])
		->toContain(EnsureFrontendRequestsAreStateful::class);
})->skip(fn () => ! class_exists(EnsureFrontendRequestsAreStateful::class), 'Sanctum not installed');
