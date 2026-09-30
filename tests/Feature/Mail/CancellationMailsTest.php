<?php

declare(strict_types=1);

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Events\SetEventState;
use App\Enums\BookingCancellationReason;
use App\Enums\EventState;
use App\Enums\InvoiceStatus;
use App\Mail\BookingCancelledCustomer;
use App\Mail\BookingCancelledInfoAdmin;
use App\Mail\BookingCancelledWithPenalty;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * The flow table's cancellation rows ([[10-mail]]): a student cancels early
 * (nothing owed), late (the penalty, invoice attached unless paid), or VIAK
 * calls the course off (these mails stay silent; *Kursabsage* speaks).
 */
beforeEach(function () {
	Storage::fake('documents');
	config(['mail.admin' => 'office@example.test']);
	$this->student = User::factory()->create();
});

function courseInDays(int $days, EventState $state = EventState::Planned): Event
{
	$event = Event::factory()
		->for(Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs'], 'fee' => '890.00']))
		->create(['date' => now()->addDays($days)->toDateString(), 'state' => $state]);
	$event->dates()->create(['date' => now()->addDays($days)->toDateString()]);

	return $event;
}

function bookThenCancel(Event $event, User $user, BookingCancellationReason $reason = BookingCancellationReason::Student)
{
	$booking = app(CreateBookingForUser::class)->execute($event, $user);
	Mail::fake();
	app(CancelBooking::class)->execute($booking->refresh(), $reason);

	return $booking->refresh();
}

it('confirms an early cancellation at no cost, and tells the office', function () {
	bookThenCancel(courseInDays(40), $this->student);

	Mail::assertQueued(BookingCancelledCustomer::class, fn ($mail) => $mail->hasTo($this->student->email));
	Mail::assertQueued(BookingCancelledInfoAdmin::class, fn ($mail) => $mail->hasTo('office@example.test'));
	Mail::assertNotQueued(BookingCancelledWithPenalty::class);
});

it('names the late cost and attaches the invoice that bills it', function () {
	$booking = bookThenCancel(courseInDays(15), $this->student);

	Mail::assertQueued(BookingCancelledWithPenalty::class, function ($mail) {
		return $mail->hasTo($this->student->email)
			&& $mail->percent === 50
			&& $mail->amount === '445.00'
			&& ! $mail->paid
			&& count($mail->attachments()) === 1;
	});
	Mail::assertNotQueued(BookingCancelledCustomer::class);
});

it('says there is nothing more to do when the invoice is paid, and attaches nothing', function () {
	$event = courseInDays(5, EventState::Confirmed);
	$booking = app(CreateBookingForUser::class)->execute($event, $this->student);
	$booking->invoice()->forceFill(['status' => InvoiceStatus::Paid, 'paid_at' => now()])->save();

	Mail::fake();
	app(CancelBooking::class)->execute($booking->refresh(), BookingCancellationReason::Student);

	Mail::assertQueued(BookingCancelledWithPenalty::class, fn ($mail) => $mail->paid && $mail->percent === 100 && $mail->attachments() === []);
});

it('stays silent when VIAK calls the course off', function () {
	$event = courseInDays(5);
	app(CreateBookingForUser::class)->execute($event, $this->student);
	Mail::fake();

	app(SetEventState::class)->execute($event->refresh(), EventState::Cancelled);

	Mail::assertNotQueued(BookingCancelledCustomer::class);
	Mail::assertNotQueued(BookingCancelledWithPenalty::class);
	Mail::assertNotQueued(BookingCancelledInfoAdmin::class);
});

it('writes legacy text', function () {
	$booking = bookThenCancel(courseInDays(15), $this->student);

	$html = (new BookingCancelledWithPenalty($booking, '445.00', 50, false, null))->render();

	expect($html)->toContain('Annullationsbestätigung – Rhino Einstiegskurs')
		->toContain('Diese belaufen sich auf CHF 445.– (50% der Kurskosten).')
		->toContain('Die entsprechende Rechnung liegt diesem Mail bei.')
		->and((new BookingCancelledCustomer($booking))->render())->toContain('/de/kurse')
		->and((new BookingCancelledInfoAdmin($booking->event, null))->render())->toContain('hat sich jemand abgemeldet');
});
