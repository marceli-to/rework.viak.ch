<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * *Passwort zurücksetzen* ([[10-mail]]): the link from *Passwort vergessen?*,
 * in German, in place of Laravel's stock notification, which legacy sent in
 * English (Marcel, 2026-09-29: translate it). The link is Fortify's own reset
 * form; its token is the broker's, valid as long as `auth.passwords` says.
 */
class PasswordReset extends VIAKMail
{
	public function __construct(
		public readonly User $user,
		public readonly string $token,
	) {
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Passwort zurücksetzen');
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.user.password-reset', with: [
			'resetUrl' => url(route('password.reset', ['token' => $this->token, 'email' => $this->user->getEmailForPasswordReset()], false)),
			'minutes' => (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
		]);
	}
}
