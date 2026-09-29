<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

/**
 * *Bestätigung Anmeldung*: the link that verifies an address ([[10-mail]]).
 * Legacy's `StudentRegistered`, sent in place of Laravel's stock mail
 * ([[User::sendEmailVerificationNotification]]). The link is Laravel's own
 * `verification.verify`, signed, for an hour.
 */
class EmailVerification extends VIAKMail
{
	public function __construct(public readonly User $user)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Bestätigung Anmeldung');
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.user.verify', with: [
			'verifyUrl' => URL::temporarySignedRoute('verification.verify', now()->addMinutes((int) config('auth.verification.expire', 60)), [
				'id' => $this->user->getKey(),
				'hash' => sha1($this->user->getEmailForVerification()),
			]),
		]);
	}
}
