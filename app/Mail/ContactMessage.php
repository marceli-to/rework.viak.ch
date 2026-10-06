<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A message from the Kontakt form, to the office ([[ContactController]]).
 * **Reply-To is the sender**, so answering is one click.
 *
 * The text is `$text`, not `$message`: a mail view's `$message` is Laravel's
 * own message object, and it wins.
 */
class ContactMessage extends VIAKMail
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
		return new Envelope(
			subject: 'Kontaktanfrage von '.$this->name,
			replyTo: [new Address($this->email, $this->name)],
		);
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.contact.message');
	}
}
