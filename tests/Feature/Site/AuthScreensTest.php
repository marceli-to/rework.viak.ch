<?php

declare(strict_types=1);

use App\Enums\Gender;
use App\Enums\OperatingSystem;
use App\Enums\Role;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;

/**
 * Login, registration and the password screens ([[08-accounts]]).
 *
 * Fortify was installed with chunk 08 and left without views, so every one of
 * these routes was a **500** until the provider bound them — the checkout sits
 * behind `auth`, so this is what unblocks it ([[09-public-site]]).
 *
 * The forms are legacy's, field for field. What is asserted here is the part
 * that is not layout: that the routes render, that a registration produces the
 * user legacy's would, and that the two rules legacy got wrong are right.
 */
beforeEach(function () {
	Country::firstOrCreate(['code' => 'ch'], ['name' => 'Schweiz', 'order' => 1]);
});

function registration(array $overrides = []): array
{
	return [
		'gender' => Gender::Female->value,
		'first_name' => 'Ada',
		'last_name' => 'Lovelace',
		'phone' => '+41 44 000 00 00',
		'street' => 'Bahnhofstrasse',
		'street_no' => '1',
		'zip' => '8001',
		'city' => 'Zürich',
		'country_code' => 'ch',
		'email' => 'ada@example.test',
		'email_confirmation' => 'ada@example.test',
		'password' => 'a-long-enough-password',
		'password_confirmation' => 'a-long-enough-password',
		'operating_systems' => [OperatingSystem::Windows->value],
		'accept_tos' => '1',
		...$overrides,
	];
}

it('renders every screen Fortify routes to', function (string $url) {
	$this->get($url)->assertOk();
})->with(['/login', '/register', '/forgot-password']);

it('signs an existing student in and sends them home', function () {
	$user = User::factory()->create(['email' => 'kundin@example.test', 'password' => Hash::make('the-right-password')]);

	$this->post('/login', ['email' => 'kundin@example.test', 'password' => 'the-right-password'])
		->assertRedirect('/de');

	expect(auth()->id())->toBe($user->id);
});

/** The message is German, not the `auth.failed` key — `lang/de/auth.php` is legacy's. */
it('refuses a wrong password in German, without saying which half was wrong', function () {
	User::factory()->create(['email' => 'kundin@example.test', 'password' => Hash::make('the-right-password')]);

	$this->from('/login')
		->post('/login', ['email' => 'kundin@example.test', 'password' => 'nope'])
		->assertRedirect('/login')
		->assertSessionHasErrors([
			'email' => 'Diese Kombination aus Zugangsdaten wurde nicht in unserer Datenbank gefunden.',
		]);

	expect(auth()->check())->toBeFalse();
});

it('registers a student with every field legacy asks for', function () {
	$this->post('/register', registration())->assertRedirect('/de');

	$user = User::firstWhere('email', 'ada@example.test');

	expect($user)->not->toBeNull()
		->and($user->name)->toBe('Ada Lovelace')
		->and($user->gender)->toBe(Gender::Female)
		->and($user->zip)->toBe('8001')
		->and($user->country_code)->toBe('ch')
		->and($user->subscribe_newsletter)->toBeFalse()
		->and(collect($user->operating_systems)->pluck('value')->all())->toBe(['windows']);

	// Everyone who signs up here is a student, and nothing else.
	expect($user->roles()->map(fn (Role $role) => $role->value)->all())->toBe(['student']);
});

/**
 * `MustVerifyEmail` is on the model, and legacy signs a new account in while
 * leaving `email_verified_at` null and enforcing nothing.
 */
it('leaves a new account unverified', function () {
	$this->post('/register', registration());

	expect(User::firstWhere('email', 'ada@example.test')->email_verified_at)->toBeNull();
});

it('will not take a mistyped email confirmation', function () {
	$this->from('/register')
		->post('/register', registration(['email_confirmation' => 'adaa@example.test']))
		->assertSessionHasErrors('email');

	expect(User::where('email', 'ada@example.test')->exists())->toBeFalse();
});

it('requires the terms and at least one operating system', function () {
	$this->from('/register')->post('/register', registration(['accept_tos' => null]))
		->assertSessionHasErrors('accept_tos');

	$this->from('/register')->post('/register', registration(['operating_systems' => []]))
		->assertSessionHasErrors('operating_systems');

	expect(User::where('email', 'ada@example.test')->exists())->toBeFalse();
});

/**
 * Legacy has none at all, and one account-takeover hole is already on record.
 *
 * The sixth attempt is a bare **429**, not a redirect carrying
 * `auth.throttle` — Fortify throttles through route middleware rather than
 * through the validator, so legacy's German throttle message never gets a
 * chance to render. Worth a friendly 429 view; noted in `Open-Questions.md`.
 */
it('rate limits login attempts after five', function () {
	User::factory()->create(['email' => 'kundin@example.test', 'password' => Hash::make('the-right-password')]);

	foreach (range(1, 5) as $attempt) {
		$this->from('/login')
			->post('/login', ['email' => 'kundin@example.test', 'password' => 'nope'])
			->assertRedirect('/login');
	}

	$this->from('/login')
		->post('/login', ['email' => 'kundin@example.test', 'password' => 'nope'])
		->assertStatus(429);
});

it('keeps two-factor and passkeys off, as the live site has neither', function () {
	expect(config('fortify.features'))
		->not->toContain(Features::twoFactorAuthentication())
		->and(collect(config('fortify.features'))->flatten()->all())
		->not->toContain('two-factor-authentication');

	$this->get('/two-factor-challenge')->assertNotFound();
});

it('drops a role_user row exactly once per registration', function () {
	$this->post('/register', registration());

	$user = User::firstWhere('email', 'ada@example.test');

	expect(DB::table('role_user')->where('user_id', $user->id)->count())->toBe(1);
});
