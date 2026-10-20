<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\LicenceOrder;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * *Neue Softwarebestellung …* to the office, a shop order ([[13-checkout]],
 * [[10-mail]]): who, what with article numbers and hosts, where the licences
 * go, and whether it is paid, so VIAK knows when to order from the reseller
 * (#45). The order's dashboard page is linked.
 */
class LicenceOrderInfoAdmin extends VIAKMail
{
	public function __construct(public readonly LicenceOrder $order)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Neue Softwarebestellung '.$this->order->number);
	}

	public function content(): Content
	{
		$invoice = $this->order->invoice;

		return new Content(markdown: 'mail.licence.order', with: [
			'order' => $this->order,
			'recipient' => 'office',
			'user' => $this->order->user,
			'lines' => $this->order->items,
			'amount' => $invoice ? 'CHF '.$invoice->grand_total.' (inkl. '.config('invoice.vat_label').')' : 'kostenlos',
			'paid' => $this->order->isPaid(),
			'invoice' => $invoice,
			'payment' => null,
			'dashboard' => url('/dashboard/bestellung/'.$this->order->uuid),
		]);
	}
}
