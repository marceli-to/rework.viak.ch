<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Booking;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * VIAK called a course date off ([[SetEventState]]). The seats are already
 * given up, without cost ([[CancelBookingsForEvent]]); `$bookings` are the ones
 * that were, so the mails know whom to tell ([[10-mail]]).
 */
class EventCancelled
{
	use Dispatchable;

	/** @param  Collection<int, Booking>  $bookings */
	public function __construct(
		public readonly Event $event,
		public readonly Collection $bookings,
	) {}
}
