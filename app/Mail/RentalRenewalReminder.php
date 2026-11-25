<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\LicenceOrderItem;
use App\Support\RentalRenewals;
use App\Support\SiteUrl;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * *Deine Jahresmietlizenz läuft bald ab* to the customer, sent by hand from
 * *Mietlizenzen, die bald auslaufen* ([[RentalRenewals]], #83). New: neither
 * legacy nor the shop reminded anyone. A renewal is ordering again, so the
 * button opens the licence's block on its Angebot's licence page, or the
 * software list where the line has no variant on the site.
 */
class RentalRenewalReminder extends VIAKMail
{
	public function __construct(public readonly LicenceOrderItem $item)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Deine Jahresmietlizenz läuft bald ab');
	}

	public function content(): Content
	{
		$order = $this->item->order;
		$product = $this->item->variant?->product;

		return new Content(markdown: 'mail.licence.renewal', with: [
			'user' => $order->user,
			'line' => $this->item->description(),
			'number' => $order->number,
			'ordered' => $order->created_at->format('d.m.Y'),
			'ends' => RentalRenewals::endsOn($this->item)->format('d.m.Y'),
			'renew' => url($product ? SiteUrl::licence($product, 'de') : SiteUrl::softwareIndex('de')),
		]);
	}
}
