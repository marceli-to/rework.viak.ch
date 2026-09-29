<?php

declare(strict_types=1);

use App\Mail\PasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * *Passwort vergessen?* sends VIAK's German mail, not Laravel's English one
 * (Marcel, 2026-09-29), and its link opens the reset form.
 */
it('sends the reset link in German, and the link works', function () {
	Mail::fake();
	$user = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Muster', 'email' => 'anna@example.test']);

	$this->post(route('password.email'), ['email' => 'anna@example.test'])->assertSessionHasNoErrors();

	$sent = null;
	Mail::assertQueued(PasswordReset::class, function ($mail) use (&$sent) {
		$sent = $mail;

		return $mail->hasTo('anna@example.test');
	});

	$html = $sent->render();
	expect($html)->toContain('Passwort zurücksetzen')
		->toContain('Guten Tag Anna Muster')
		->toContain('Dieser Link ist 60 Minuten gültig.')
		->not->toContain('Reset Password');

	preg_match('#href="([^"]*/password/reset/[^"]+)"#', $html, $link);
	$this->get(html_entity_decode($link[1]))->assertOk()->assertSee('Neues Passwort');
});
