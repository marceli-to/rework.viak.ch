<?php

declare(strict_types=1);

use App\Models\Country;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

/**
 * *Mein Profil* on the dashboard — `/api/admin/profile` ([[07-dashboard]],
 * step 6), through the portal's [[UpdateProfile]].
 */
beforeEach(function () {
	Country::firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
	$this->admin = User::factory()->admin()->create([
		'email' => 'admin@example.test', 'gender' => 'female', 'country_code' => 'ch', 'password' => Hash::make('altes-passwort'),
	]);
});

it('keeps the dashboard profile to admins', function () {
	$this->actingAs(User::factory()->student()->create())->getJson('/api/admin/profile')->assertForbidden();
});

it('sends back exactly what it loads, the password fields empty', function () {
	$form = $this->actingAs($this->admin)->getJson('/api/admin/profile')->json('data');

	expect($form['password'])->toBe('')
		->and($this->putJson('/api/admin/profile', $form)->assertOk()->json('data'))->toBe($form);
});

it('changes the details without asking for the password', function () {
	$form = $this->actingAs($this->admin)->getJson('/api/admin/profile')->json('data');

	$this->putJson('/api/admin/profile', [...$form, 'city' => 'Winterthur'])->assertOk();

	expect($this->admin->refresh()->city)->toBe('Winterthur');
});

it('asks for the current password to change the address or the password', function () {
	$form = $this->actingAs($this->admin)->getJson('/api/admin/profile')->json('data');

	$this->putJson('/api/admin/profile', [...$form, 'password' => 'neues-passwort', 'password_confirmation' => 'neues-passwort'])
		->assertJsonPath('errors.current_password.0', 'Bitte dein aktuelles Passwort eingeben, um E-Mail oder Passwort zu ändern.');

	$this->putJson('/api/admin/profile', [...$form, 'email' => 'neu@example.test', 'current_password' => 'falsch'])
		->assertJsonPath('errors.current_password.0', 'Das Passwort ist nicht korrekt.');

	expect($this->admin->refresh()->email)->toBe('admin@example.test');
});

it('changes the password with the current one', function () {
	$form = $this->actingAs($this->admin)->getJson('/api/admin/profile')->json('data');

	$this->putJson('/api/admin/profile', [...$form, 'password' => 'neues-passwort', 'password_confirmation' => 'neues-passwort', 'current_password' => 'altes-passwort'])->assertOk();

	expect(Hash::check('neues-passwort', $this->admin->refresh()->password))->toBeTrue();
});

it('has a new address confirmed again', function () {
	Notification::fake();
	$form = $this->actingAs($this->admin)->getJson('/api/admin/profile')->json('data');

	$this->putJson('/api/admin/profile', [...$form, 'email' => 'neu@example.test', 'current_password' => 'altes-passwort'])
		->assertOk()
		->assertJsonPath('data.email_verified', false);

	Notification::assertSentTo($this->admin->refresh(), VerifyEmail::class);
});
