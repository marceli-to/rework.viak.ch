<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RentalChanged;
use App\Mail\RentalAdded;
use App\Mail\RentalAddedInfoAdmin;
use App\Mail\RentalCancelledInfoAdmin;
use Illuminate\Support\Facades\Mail;

/**
 * A laptop added to a seat or dropped from it ([[10-mail]]) — legacy's
 * `RentalAddedHandler` and `RentalCancelledHandler`: added, the student and
 * the office hear of it; dropped, the office.
 */
class SendRentalMails
{
	public function handle(RentalChanged $changed): void
	{
		$booking = $changed->booking->loadMissing(['user', 'event.course', 'event.dates']);
		$office = config('mail.admin');

		if ($changed->added) {
			Mail::to($booking->user)->send(new RentalAdded($booking));
		}

		if ($office) {
			Mail::to($office)->send($changed->added ? new RentalAddedInfoAdmin($booking) : new RentalCancelledInfoAdmin($booking));
		}
	}
}
