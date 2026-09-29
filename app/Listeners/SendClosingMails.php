<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EventClosed;
use App\Jobs\SendParticipationConfirmation;

/**
 * A course date is closed ([[10-mail]]) — legacy's `EventClosedHandler`: each
 * seat still booked **and ticked as attended** gets the participation
 * confirmation. A seat nobody ticked gets nothing (Marcel, 2026-09-29).
 */
class SendClosingMails
{
	public function handle(EventClosed $closed): void
	{
		$event = $closed->event->loadMissing(['course', 'dates', 'experts', 'location']);

		$event->bookings()->active()->whereNotNull('participated_at')->with('user')->get()
			->each(fn ($booking) => SendParticipationConfirmation::dispatch($booking->setRelation('event', $event)));
	}
}
