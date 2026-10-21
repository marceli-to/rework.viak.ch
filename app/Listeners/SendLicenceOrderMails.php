<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LicenceOrderPlaced;
use App\Mail\LicenceOrderConfirmation;
use App\Mail\LicenceOrderInfoAdmin;
use Illuminate\Support\Facades\Mail;

/**
 * An order ([[LicenceOrderPlaced]], [[10-mail]]): the customer, with the
 * invoice, and the office. **Not the office for an order it entered itself**
 * on the dashboard (Marcel, 2026-10-21); the customer still hears, since
 * nothing else sends them the invoice.
 */
class SendLicenceOrderMails
{
	public function handle(LicenceOrderPlaced $placed): void
	{
		$order = $placed->order->loadMissing(['user', 'items', 'invoice.document']);

		Mail::to($order->user)->send(new LicenceOrderConfirmation($order));

		if ($order->entered_by === null && $office = config('mail.admin')) {
			Mail::to($office)->send(new LicenceOrderInfoAdmin($order));
		}
	}
}
