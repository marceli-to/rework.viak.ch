<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Mail\InvoicePaidConfirmation;
use App\Mail\InvoicePaidNotification;
use Illuminate\Support\Facades\Mail;

/** A card payment ([[InvoicePaid]], [[10-mail]]): legacy's `InvoicePaidHandler`, the customer and the office. */
class SendInvoicePaidMails
{
	public function handle(InvoicePaid $paid): void
	{
		$invoice = $paid->invoice->loadMissing('user');

		Mail::to($invoice->user)->send(new InvoicePaidConfirmation($invoice));

		if ($office = config('mail.admin')) {
			Mail::to($office)->send(new InvoicePaidNotification($invoice));
		}
	}
}
