<?php

declare(strict_types=1);

use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Bookings\SetRental;
use App\Enums\EventState;
use App\Mail\EventCancelOrConfirmReminder;
use App\Mail\RentalAdded;
use App\Mail\RentalAddedInfoAdmin;
use App\Mail\RentalCancelledInfoAdmin;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * The rental rows, and *10 days out, still planned* ([[10-mail]]).
 */
beforeEach(function () {
	config(['mail.admin' => 'office@example.test']);
});

function eventIn(int $days, array $attributes = []): Event
{
	$event = Event::factory()->for(Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs']]))
		->create(['date' => now()->addDays($days)->toDateString(), 'rentals_available' => 2, ...$attributes]);
	$event->dates()->create(['date' => now()->addDays($days)->toDateString()]);

	return $event;
}

it('tells the student and the office when a laptop is added later, and the office when it is dropped', function () {
	$student = User::factory()->create();
	$booking = app(CreateBookingForUser::class)->execute(eventIn(40), $student);
	Mail::fake();

	app(SetRental::class)->execute($booking->refresh(), true);

	Mail::assertQueued(RentalAdded::class, fn ($mail) => $mail->hasTo($student->email));
	Mail::assertQueued(RentalAddedInfoAdmin::class, fn ($mail) => $mail->hasTo('office@example.test'));

	Mail::fake();
	app(SetRental::class)->execute($booking->refresh(), false);

	Mail::assertQueued(RentalCancelledInfoAdmin::class, fn ($mail) => $mail->hasTo('office@example.test'));
	Mail::assertNotQueued(RentalAdded::class);
});

it('sends nothing when the rental is set to what it already is', function () {
	$booking = app(CreateBookingForUser::class)->execute(eventIn(40), User::factory()->create());
	Mail::fake();

	app(SetRental::class)->execute($booking->refresh(), false);

	Mail::assertNothingQueued();
});

it('reminds the office once of a planned date inside ten days, however late the run', function () {
	$soon = eventIn(7);
	eventIn(15);
	eventIn(5, ['state' => EventState::Confirmed]);
	Mail::fake();

	$this->artisan('events:remind')->assertSuccessful();

	Mail::assertQueued(EventCancelOrConfirmReminder::class, 1);
	Mail::assertQueued(EventCancelOrConfirmReminder::class, fn ($mail) => $mail->event->is($soon) && $mail->hasTo('office@example.test'));
	expect($soon->refresh()->reminded_at)->not->toBeNull();

	Mail::fake();
	$this->artisan('events:remind')->assertSuccessful();
	Mail::assertNothingQueued();
});

it('writes legacy text', function () {
	expect((new EventCancelOrConfirmReminder(eventIn(7)))->render())
		->toContain('Reminder – Rhino Einstiegskurs')
		->toContain('Der folgende Kurs wurde noch nicht bestätigt oder abgesagt:');
});
