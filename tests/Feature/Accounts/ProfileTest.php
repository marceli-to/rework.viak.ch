<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserDocument;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
	$this->user = User::factory()->student()->create([
		'password' => Hash::make('correct-horse'),
		'email_verified_at' => now(),
	]);
});

it('updates ordinary details without asking for a password', function () {
	$this->actingAs($this->user)
		->putJson('/api/profile', ['first_name' => 'Neu', 'last_name' => 'Name'])
		->assertOk();

	expect($this->user->refresh()->first_name)->toBe('Neu');
});

/**
 * Legacy asked for the current password nowhere, on any of its three copies of
 * this form. A session left open on a shared machine was enough to take an
 * account over permanently.
 */
it('will not change a password without the current one', function () {
	$this->actingAs($this->user)
		->putJson('/api/profile', [
			'first_name' => 'A', 'last_name' => 'B',
			'password' => 'new-password', 'password_confirmation' => 'new-password',
		])
		->assertStatus(422)
		->assertJsonValidationErrors('current_password');
});

it('will not change an email address without the current password', function () {
	$this->actingAs($this->user)
		->putJson('/api/profile', ['first_name' => 'A', 'last_name' => 'B', 'email' => 'neu@example.com'])
		->assertStatus(422)
		->assertJsonValidationErrors('current_password');
});

it('changes the password when the current one is given', function () {
	$this->actingAs($this->user)
		->putJson('/api/profile', [
			'first_name' => 'A', 'last_name' => 'B',
			'password' => 'new-password', 'password_confirmation' => 'new-password',
			'current_password' => 'correct-horse',
		])
		->assertOk();

	expect(Hash::check('new-password', $this->user->refresh()->password))->toBeTrue();
});

it('rejects a wrong current password', function () {
	$this->actingAs($this->user)
		->putJson('/api/profile', [
			'first_name' => 'A', 'last_name' => 'B',
			'email' => 'neu@example.com', 'current_password' => 'wrong',
		])
		->assertStatus(422)
		->assertJsonValidationErrors('current_password');

	expect($this->user->refresh()->email)->not->toBe('neu@example.com');
});

/**
 * The finding this exists for. Legacy set the new address and left
 * `email_verified_at` alone, so it silently inherited verified status without
 * ever being proven — and mail then went to an unconfirmed address.
 */
it('makes a new email address prove itself again', function () {
	Notification::fake();

	$this->actingAs($this->user)
		->putJson('/api/profile', [
			'first_name' => 'A', 'last_name' => 'B',
			'email' => 'neu@example.com', 'current_password' => 'correct-horse',
		])
		->assertOk()
		->assertJsonPath('email_verification_required', true);

	expect($this->user->refresh()->email)->toBe('neu@example.com')
		->and($this->user->hasVerifiedEmail())->toBeFalse();

	Notification::assertSentTo($this->user, VerifyEmail::class);
});

it('keeps verification when the address has not changed', function () {
	$this->actingAs($this->user)
		->putJson('/api/profile', [
			'first_name' => 'A', 'last_name' => 'B',
			'email' => $this->user->email, 'current_password' => 'correct-horse',
		])
		->assertOk();

	expect($this->user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('lists a customer’s own documents with a route to download them', function () {
	UserDocument::factory()->for($this->user)->count(2)->create();
	UserDocument::factory()->count(3)->create();

	$response = $this->actingAs($this->user)->getJson('/api/profile/documents')->assertOk();

	expect($response->json('data'))->toHaveCount(2)
		->and($response->json('data.0.download_url'))->toContain('/dokumente/');
});

it('keeps invoice addresses to their owner', function () {
	$mine = UserAddress::factory()->for($this->user)->create();
	$theirs = UserAddress::factory()->create();

	$this->actingAs($this->user)->getJson('/api/addresses')->assertJsonCount(1, 'data');
	$this->actingAs($this->user)->putJson("/api/addresses/{$mine->uuid}", [
		'first_name' => 'A', 'last_name' => 'B', 'street' => 'S',
		'zip' => '8000', 'city' => 'Zürich', 'country_code' => 'CH',
	])->assertOk();
	$this->actingAs($this->user)->deleteJson("/api/addresses/{$theirs->uuid}")->assertForbidden();
});
