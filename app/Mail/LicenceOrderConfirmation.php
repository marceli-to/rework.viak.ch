<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\AttachesDocument;
use App\Models\LicenceOrder;
use App\Support\SiteUrl;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * *Bestellbestätigung – Bestellung …* to the customer, a shop order
 * ([[13-checkout]], [[10-mail]]). New: legacy sold no licences.
 *
 * With its invoice attached. **Worded at sending, not at ordering**: the mail
 * is queued, and the customer is usually on Stripe's page meanwhile, so it
 * says paid when it is, and offers the card only when it is not.
 */
class LicenceOrderConfirmation extends VIAKMail
{
	use AttachesDocument;

	public function __construct(public readonly LicenceOrder $order)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Bestellbestätigung – Bestellung '.$this->order->number);
	}

	public function content(): Content
	{
		$invoice = $this->order->invoice;

		return new Content(markdown: 'mail.licence.order', with: [
			'order' => $this->order,
			'recipient' => 'customer',
			'user' => $this->order->user,
			'lines' => $this->order->items,
			'amount' => $invoice ? 'CHF '.$invoice->grand_total.' (inkl. '.config('invoice.vat_label').')' : 'kostenlos',
			'paid' => $this->order->isPaid(),
			'invoice' => $invoice,
			'payment' => $invoice && ! $this->order->isPaid() ? url(SiteUrl::invoicePayment($invoice->uuid, 'de')) : null,
			'portal' => url(SiteUrl::customerPortal('de')),
		]);
	}

	/** @return array<int, Attachment> */
	public function attachments(): array
	{
		return $this->attachDocument($this->order->invoice?->document);
	}
}
