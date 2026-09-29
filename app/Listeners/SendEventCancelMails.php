<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EventCancelled;
use App\Mail\EventCancelExpert;
use App\Mail\EventCancelStudent;
use Illuminate\Support\Facades\Mail;

/**
 * A course date is called off ([[10-mail]]) — legacy's `EventCancelledHandler`:
 * *Kursabsage* to every student whose seat went with it, and to every expert.
 * The students' own cancellation mails stay silent for this
 * ([[SendCancellationMails]]).
 */
class SendEventCancelMails
{
	public function handle(EventCancelled $cancelled): void
	{
		$event = $cancelled->event->loadMissing(['course', 'dates', 'experts']);

		foreach ($cancelled->bookings as $booking) {
			$booking->loadMissing('user')->setRelation('event', $event);
			Mail::to($booking->user)->send(new EventCancelStudent($booking));
		}

		foreach ($event->experts as $expert) {
			Mail::to($expert)->send(new EventCancelExpert($event));
		}
	}
}
