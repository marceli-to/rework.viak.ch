<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use App\Support\AccountInvite;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * *Dein VIAK-Zugang* to a person an admin created ([[CreateAccount]], [[10-mail]]).
 * Legacy's `ExpertCreated`, to students as well now (#26). The link is made
 * when the mail is written, so a queued mail carries a link that still works.
 */
class AccountInvitation extends VIAKMail
{
	public function __construct(public readonly User $user)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Dein VIAK-Zugang '.config('app.name'));
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.user.invite', with: [
			'confirmUrl' => AccountInvite::url($this->user),
		]);
	}
}
