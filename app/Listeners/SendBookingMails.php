<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\EventState;
use App\Events\BookingMade;
use App\Jobs\SendCourseConfirmation;
use App\Mail\BookingCompleted;
use App\Mail\BookingCreatedInfo;
use App\Mail\EventMessageCustomer;
use App\Mail\RentalAddedInfoAdmin;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Everything a booking sends ([[10-mail]], the flow table's first row) —
 * legacy's `BookingCompletedHandler`, plus the earlier messages its checkout
 * sent a late booker (`Message::past`):
 *
 * 1. the student: *Buchungsbestätigung*;
 * 2. each expert of the date, and the office: *Neue Anmeldung*;
 * 3. the office, if a laptop came with it: *Buchung Mietcomputer*;
 * 4. the student, if the course is already confirmed: *Kursbestätigung*
 *    with the invoice, which the booking raised before this ran
 *    ([[CompleteCheckout]]);
 * 5. the student, if the course already has messages: each of them, one mail
 *    per message as legacy does (Marcel, 2026-09-24), recorded as sent.
 */
class SendBookingMails
{
	public function handle(BookingMade $made): void
	{
		$booking = $made->booking->loadMissing(['user', 'event.course', 'event.experts', 'event.dates', 'event.location']);
		$event = $booking->event;
		$student = $booking->user;

		Mail::to($student)->send(new BookingCompleted($booking));

		foreach ($event->experts as $expert) {
			Mail::to($expert)->send(new BookingCreatedInfo($booking, $expert, toExpert: true));
		}

		if ($office = config('mail.admin')) {
			$officeUser = User::query()->where('email', $office)->first();
			Mail::to($office)->send(new BookingCreatedInfo($booking, $officeUser, toExpert: false));

			if ($booking->has_rental) {
				Mail::to($office)->send(new RentalAddedInfoAdmin($booking));
			}
		}

		if ($event->state === EventState::Confirmed) {
			SendCourseConfirmation::dispatch($booking);
		}

		foreach ($event->messages()->with(['author', 'media'])->orderBy('created_at')->orderBy('id')->get() as $post) {
			Mail::to($student)->send(new EventMessageCustomer($post));
			$post->recipients()->syncWithoutDetaching([$student->id => ['created_at' => now()]]);
		}
	}
}
