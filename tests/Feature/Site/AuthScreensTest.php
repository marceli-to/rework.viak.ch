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

/**
 * **At legacy's URLs, not Fortify's.** The live site registers at
 * `/de/registration` and resets at `/password/reset`; Fortify would serve both
 * somewhere else, and `config/fortify.php`'s `paths` moves them.
 */
it('renders every screen at the URL the live site uses', function (string $url) {
	$this->get($url)->assertOk();
})->with(['/login', '/de/registration', '/password/reset']);

it('keeps legacy’s redirect from the unprefixed register URL', function () {
	$this->get('/register')->assertRedirect('/de/registration');
});

it('does not answer on Fortify’s own paths, which the live site never had', function (string $url) {
	$this->get($url)->assertNotFound();
})->with(['/forgot-password', '/reset-password/some-token']);

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
	$this->post('/de/registration', registration())->assertRedirect('/de');

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
	$this->post('/de/registration', registration());

	expect(User::firstWhere('email', 'ada@example.test')->email_verified_at)->toBeNull();
});

it('will not take a mistyped email confirmation', function () {
	$this->from('/de/registration')
		->post('/de/registration', registration(['email_confirmation' => 'adaa@example.test']))
		->assertSessionHasErrors('email');

	expect(User::where('email', 'ada@example.test')->exists())->toBeFalse();
});

it('requires the terms and at least one operating system', function () {
	$this->from('/de/registration')->post('/de/registration', registration(['accept_tos' => null]))
		->assertSessionHasErrors('accept_tos');

	$this->from('/de/registration')->post('/de/registration', registration(['operating_systems' => []]))
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
	$this->post('/de/registration', registration());

	$user = User::firstWhere('email', 'ada@example.test');

	expect(DB::table('role_user')->where('user_id', $user->id)->count())->toBe(1);
});

/**
 * **The `guest` middleware ignores `fortify.home`.** It hunts for a route named
 * `dashboard` (`RedirectIfAuthenticated::defaultRedirectUri()`) and this app has
 * one — the SPA shell — so a signed-in student who opened `/login` was thrown
 * into the admin dashboard, which then landed on `/dashboard/termine`.
 */
it('sends a signed-in student away from the login page to the public site', function () {
    $student = User::factory()->create();
    DB::table('role_user')->insert(['user_id' => $student->id, 'role' => Role::Student->value]);

    $this->actingAs($student)->get('/login')->assertRedirect('/de');
    $this->actingAs($student)->get('/de/registration')->assertRedirect('/de');
});

it('sends signed-in staff to the dashboard instead', function () {
    $admin = User::factory()->create();
    DB::table('role_user')->insert(['user_id' => $admin->id, 'role' => Role::Admin->value]);

    $this->actingAs($admin)->get('/login')->assertRedirect('/dashboard');
});

it('lands a student on the public site after logging in, and staff on the dashboard', function () {
    $student = User::factory()->create(['password' => Hash::make('pw-for-the-student')]);
    DB::table('role_user')->insert(['user_id' => $student->id, 'role' => Role::Student->value]);

    $this->post('/login', ['email' => $student->email, 'password' => 'pw-for-the-student'])
        ->assertRedirect('/de');

    auth()->logout();

    $admin = User::factory()->create(['password' => Hash::make('pw-for-the-admin')]);
    DB::table('role_user')->insert(['user_id' => $admin->id, 'role' => Role::Admin->value]);

    $this->post('/login', ['email' => $admin->email, 'password' => 'pw-for-the-admin'])
        ->assertRedirect('/dashboard');
});

/** What carries a guest back to the checkout step that bounced them. */
it('returns to the page that asked for the login', function () {
    $student = User::factory()->create(['password' => Hash::make('pw-for-the-student')]);
    DB::table('role_user')->insert(['user_id' => $student->id, 'role' => Role::Student->value]);

    $this->withSession(['url.intended' => '/de/checkout/basket'])
        ->post('/login', ['email' => $student->email, 'password' => 'pw-for-the-student'])
        ->assertRedirect('/de/checkout/basket');
});

/**
 * Three parity defects found on 2026-09-22 while building the portal's profile
 * form, which is this form's twin — and all three confirmed on the live
 * `/de/registration`, which is public ([[09-public-site]]).
 *
 * The gender labels are the one worth pinning. *Frau / Herr / Divers* is
 * defensible as copy — the field exists for the salutation on an invoice
 * ([[Gender]]) — but it was neither measured nor recorded, and the portal's own
 * form would have disagreed with it. Both read `Gender::label()` now.
 */
it('offers the three gender labels production serves, from one place', function () {
	$this->get('/de/registration')
		->assertOk()
		->assertSee('männlich')
		->assertSee('weiblich')
		->assertSee('andere')
		->assertDontSee('Divers');
});

it('splits street/number and zip/city in half, as production does', function () {
	$html = $this->get('/de/registration')->assertOk()->getContent();

	// `span-6` twice, not 9/3 and not 4/8 — 329px each of the 1068 column.
	expect(substr_count($html, 'sm:col-span-6'))->toBe(4)
		->and($html)->not->toContain('sm:col-span-9')
		->and($html)->not->toContain('sm:col-span-3');
});
