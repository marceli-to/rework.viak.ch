<?php

declare(strict_types=1);

use App\Actions\Accounts\CreateAccount;
use App\Enums\Role;
use App\Mail\AccountInvitation;
use App\Mail\EmailVerification;
use App\Models\User;
use App\Support\AccountInvite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * *Student registers* and *admin creates an account* ([[10-mail]]).
 */
it('invites a person an admin created to set their own password', function () {
	Mail::fake();

	$user = app(CreateAccount::class)->execute(['first_name' => 'Anna', 'last_name' => 'Muster', 'email' => 'anna@example.test'], [Role::Expert]);

	Mail::assertQueued(AccountInvitation::class, fn ($mail) => $mail->hasTo('anna@example.test') && $mail->user->is($user));
});

it('lets the invited person set a password once, and signs them in', function () {
	$user = User::factory()->expert()->create(['email_verified_at' => null]);
	$url = AccountInvite::url($user);

	$this->get($url)->assertOk()->assertSee('Passwort festlegen');

	$this->post($url, ['password' => 'ein-neues-passwort', 'password_confirmation' => 'ein-neues-passwort'])->assertRedirect();

	expect(Hash::check('ein-neues-passwort', $user->refresh()->password))->toBeTrue()
		->and($user->hasVerifiedEmail())->toBeTrue();
	$this->assertAuthenticatedAs($user);

	auth()->logout();
	$this->get($url)->assertForbidden();
});

it('refuses a link that was tampered with or has expired', function () {
	$user = User::factory()->expert()->create();
	$url = AccountInvite::url($user);
	$other = User::factory()->expert()->create();

	$this->get(str_replace($user->uuid, $other->uuid, $url))->assertForbidden();

	$this->travel(AccountInvite::HOURS + 1)->hours();
	$this->get($url)->assertForbidden();
});

it('refuses a deactivated account', function () {
	$user = User::factory()->expert()->create(['deactivated_at' => now()]);

	$this->get(AccountInvite::url($user))->assertForbidden();
});

it('sends VIAK own verification mail on registration, in legacy words', function () {
	Mail::fake();
	$user = User::factory()->unverified()->create(['first_name' => 'Anna', 'last_name' => 'Muster']);

	$user->sendEmailVerificationNotification();

	Mail::assertQueued(EmailVerification::class, function ($mail) use ($user) {
		$html = $mail->render();

		return $mail->hasTo($user->email)
			&& str_contains($html, 'Guten Tag Anna Muster')
			&& str_contains($html, '/email/verify/'.$user->id.'/');
	});
});

it('writes the invite in legacy words, with the link', function () {
	$user = User::factory()->expert()->create(['first_name' => 'Kevin', 'last_name' => 'Betschart']);

	expect((new AccountInvitation($user))->render())
		->toContain('Es wurde ein Konto für Dich eingerichtet.')
		->toContain('/zugang/'.$user->uuid);
});
