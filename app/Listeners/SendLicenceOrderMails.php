<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LicenceOrderPlaced;
use App\Mail\LicenceOrderConfirmation;
use App\Mail\LicenceOrderInfoAdmin;
use Illuminate\Support\Facades\Mail;

/** A shop order ([[LicenceOrderPlaced]], [[10-mail]]): the customer, with the invoice, and the office. */
class SendLicenceOrderMails
{
	public function handle(LicenceOrderPlaced $placed): void
	{
		$order = $placed->order->loadMissing(['user', 'items', 'invoice.document']);

		Mail::to($order->user)->send(new LicenceOrderConfirmation($order));

		if ($office = config('mail.admin')) {
			Mail::to($office)->send(new LicenceOrderInfoAdmin($order));
		}
	}
}
