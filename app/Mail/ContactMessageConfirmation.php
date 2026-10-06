<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * *Danke für deine Nachricht*, to whoever sent the Kontakt form, with what they
 * wrote ([[ContactController]]).
 */
class ContactMessageConfirmation extends VIAKMail
{
	public function __construct(
		public readonly string $name,
		public readonly string $email,
		public readonly string $text,
	) {
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Danke für deine Nachricht');
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.contact.confirmation');
	}
}
