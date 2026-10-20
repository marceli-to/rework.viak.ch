<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use App\Support\SiteUrl;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** *Zahlungsbestätigung Rechnung …* to the customer, after a card payment ([[10-mail]]). Legacy's own. */
class InvoicePaidConfirmation extends VIAKMail
{
	public function __construct(public readonly Invoice $invoice)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Zahlungsbestätigung Rechnung '.$this->invoice->number);
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.invoice.paid', with: [
			'recipient' => 'customer',
			'paidOn' => $this->invoice->paid_at?->format('d.m.Y'),
			'portal' => url(SiteUrl::customerPortal('de')),
		]);
	}
}
