<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EventConfirmed;
use App\Jobs\SendCourseConfirmation;
use App\Mail\EventConfirmationExpert;
use Illuminate\Support\Facades\Mail;

/**
 * A course date is confirmed ([[10-mail]]) — legacy's `EventConfirmedHandler`:
 * every student holding a seat gets *Kursbestätigung* with their invoice
 * ([[SendCourseConfirmation]]), every expert *Bestätigung*.
 */
class SendConfirmationMails
{
	public function handle(EventConfirmed $confirmed): void
	{
		$event = $confirmed->event->loadMissing(['course', 'dates', 'experts', 'location']);

		foreach ($event->bookings()->active()->with('user')->get() as $booking) {
			SendCourseConfirmation::dispatch($booking->setRelation('event', $event));
		}

		foreach ($event->experts as $expert) {
			Mail::to($expert)->send(new EventConfirmationExpert($event, $expert));
		}
	}
}
