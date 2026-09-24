<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Who reaches the dashboard ([[07-dashboard]]).
 *
 * Until 2026-09-24 the shell had no middleware at all: the API behind it
 * checked policies, but the page loaded for anyone.
 */
it('sends a guest to the login', function () {
	$this->get('/dashboard/termine')->assertRedirect(route('login'));
});

it('lets an admin in, on any path the SPA owns', function () {
	$admin = User::factory()->admin()->create(['email_verified_at' => now()]);

	$this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('id="app"', false);
	$this->actingAs($admin)->get('/dashboard/kurse')->assertOk();
});

it('sends a student or an expert to their own portal instead of a 403', function () {
	$student = User::factory()->student()->create(['email_verified_at' => now()]);
	$expert = User::factory()->expert()->create(['email_verified_at' => now()]);

	$this->actingAs($student)->get('/dashboard')->assertRedirect('/de/student/profil');
	$this->actingAs($expert)->get('/dashboard/termine')->assertRedirect('/de/experte/profil');
});

it('lands each role in the right place after signing in', function (string $state, string $landing) {
	$user = User::factory()->{$state}()->create(['email_verified_at' => now(), 'password' => 'geheim1234']);

	$this->post('/login', ['email' => $user->email, 'password' => 'geheim1234'])
		->assertRedirect($landing);
})->with([
	'admin' => ['admin', '/dashboard'],
	// The dashboard is admins-only now, so an expert goes to their portal.
	'expert' => ['expert', '/de/experte/profil'],
	'student' => ['student', '/de'],
]);
