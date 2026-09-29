<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\BookingCancellationReason;
use App\Events\BookingCancelled;
use App\Jobs\SendCancellationConfirmation;
use App\Mail\BookingCancelledInfoAdmin;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * A student gave up a seat, or an admin did for them ([[10-mail]]) — legacy's
 * two cancellation handlers: the student's confirmation, and *Abmeldung* to
 * the office.
 *
 * **Not when VIAK called the course off.** [[CancelBookingsForEvent]] cancels
 * every seat through the same Action, and the students are told by the
 * course's own *Kursabsage*, as legacy's `EventCancelledHandler` did, not by
 * one confirmation each of a cancellation they never made.
 */
class SendCancellationMails
{
	public function handle(BookingCancelled $cancelled): void
	{
		$booking = $cancelled->booking->loadMissing(['event.course', 'event.dates', 'event.location']);

		if ($booking->cancellation_reason === BookingCancellationReason::EventCancelled) {
			return;
		}

		SendCancellationConfirmation::dispatch($booking);

		if ($office = config('mail.admin')) {
			Mail::to($office)->send(new BookingCancelledInfoAdmin($booking->event, User::query()->where('email', $office)->first()));
		}
	}
}
