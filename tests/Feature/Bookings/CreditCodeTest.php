<?php

declare(strict_types=1);

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Events\SetEventState;
use App\Enums\BookingCancellationReason;
use App\Enums\EventState;
use App\Enums\InvoiceStatus;
use App\Mail\BookingCancelledStudent;
use App\Mail\BookingCancelledWithPenalty;
use App\Mail\EventCancelStudent;
use App\Models\Booking;
use App\Models\Course;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Money already paid for a seat given up comes back as a code (Marcel,
 * 2026-09-29, legacy's behaviour), good once where legacy's was not.
 */
beforeEach(function () {
	Storage::fake('documents');
	$this->student = User::factory()->student()->create();
});

function paidSeat(int $days, User $student): Booking
{
	$event = Event::factory()->for(Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs'], 'fee' => '890.00']))
		->create(['date' => now()->addDays($days)->toDateString(), 'state' => EventState::Confirmed]);
	$event->dates()->create(['date' => now()->addDays($days)->toDateString()]);

	$booking = app(CreateBookingForUser::class)->execute($event, $student)->refresh();
	$booking->invoice()->forceFill(['status' => InvoiceStatus::Paid, 'paid_at' => now()])->save();

	return $booking;
}

it('credits the whole paid amount when cancelling costs nothing, for a year, good once', function () {
	$booking = paidSeat(40, $this->student);
	Mail::fake();

	app(CancelBooking::class)->execute($booking, BookingCancellationReason::Student);

	$code = DiscountCode::where('booking_id', $booking->id)->sole();
	expect($code->amount)->toBe('890.00')
		->and($code->usage_limit)->toBe(1)
		->and($code->valid_to->toDateString())->toBe(today()->addYear()->toDateString());

	Mail::assertQueued(BookingCancelledStudent::class, fn ($mail) => str_contains($mail->render(), $code->code)
		&& str_contains($mail->render(), 'Falls du lieber eine Rückerstattung des Betrages möchtest'));
});

it('credits what was paid over the cost of a late cancellation', function () {
	$booking = paidSeat(15, $this->student);
	Mail::fake();

	app(CancelBooking::class)->execute($booking, BookingCancellationReason::Student);

	$code = DiscountCode::where('booking_id', $booking->id)->sole();
	expect($code->amount)->toBe('445.00');

	Mail::assertQueued(BookingCancelledWithPenalty::class, fn ($mail) => str_contains($mail->render(), 'für den zuviel bezahlten Betrag')
		&& str_contains($mail->render(), $code->code));
});

it('credits nothing when the paid amount is the cost', function () {
	$booking = paidSeat(5, $this->student);
	Mail::fake();

	app(CancelBooking::class)->execute($booking, BookingCancellationReason::Student);

	expect(DiscountCode::where('booking_id', $booking->id)->exists())->toBeFalse();
	Mail::assertQueued(BookingCancelledWithPenalty::class, fn ($mail) => str_contains($mail->render(), 'musst Du nichts weiter unternehmen'));
});

it('credits nothing for an invoice not yet paid', function () {
	$booking = paidSeat(40, $this->student);
	$booking->invoice()->forceFill(['status' => InvoiceStatus::Open, 'paid_at' => null])->save();

	app(CancelBooking::class)->execute($booking, BookingCancellationReason::Student);

	expect(DiscountCode::where('booking_id', $booking->id)->exists())->toBeFalse();
});

it('credits every paid seat on a course VIAK calls off, and says so in the Kursabsage', function () {
	$booking = paidSeat(5, $this->student);
	Mail::fake();

	app(SetEventState::class)->execute($booking->event, EventState::Cancelled);

	$code = DiscountCode::where('booking_id', $booking->id)->sole();
	expect($code->amount)->toBe('890.00');
	Mail::assertQueued(EventCancelStudent::class, fn ($mail) => str_contains($mail->render(), $code->code));
});
