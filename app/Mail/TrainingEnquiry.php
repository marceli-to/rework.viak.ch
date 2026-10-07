<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A Firmenschulung enquiry, to the office ([[TrainingController]]), drawn as
 * Kontakt's is ([[ContactMessage]]). Reply-To is the contact person.
 */
class TrainingEnquiry extends VIAKMail
{
	public function __construct(
		public readonly string $company,
		public readonly string $name,
		public readonly string $email,
		public readonly ?string $phone,
		public readonly string $text,
	) {
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(
			subject: 'Anfrage Firmenschulung von '.$this->company,
			replyTo: [new Address($this->email, $this->name)],
		);
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.training.enquiry');
	}
}
