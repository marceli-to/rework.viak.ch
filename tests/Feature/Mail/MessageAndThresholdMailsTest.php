<?php

declare(strict_types=1);

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Messages\PostMessage;
use App\Enums\BookingCancellationReason;
use App\Mail\EventMessageExpert;
use App\Mail\EventMessageStudent;
use App\Mail\ParticipantsChange;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * *Expert posts a message* and the three threshold rows ([[10-mail]]).
 */
beforeEach(function () {
	config(['mail.admin' => 'office@example.test']);
	$this->event = Event::factory()->for(Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs']]))
		->create(['min_participants' => 2, 'max_participants' => 3]);
	$this->event->dates()->create(['date' => now()->addMonth()->toDateString()]);
	$this->expert = User::factory()->expert()->create();
	$this->event->experts()->attach($this->expert);
});

function seat(Event $event): User
{
	$student = User::factory()->student()->create();
	app(CreateBookingForUser::class)->execute($event->refresh(), $student);

	return $student;
}

it('mails a posted message to each booked student, and a copy to the author who asked', function () {
	$anna = seat($this->event);
	$beat = seat($this->event);
	Mail::fake();

	app(PostMessage::class)->execute($this->event, $this->expert, 'Vorbereitung', '<p>Bitte Laptop mitbringen.</p>', copyToAuthor: true);

	Mail::assertQueued(EventMessageStudent::class, fn ($mail) => $mail->hasTo($anna->email) && ! $mail instanceof EventMessageExpert);
	Mail::assertQueued(EventMessageStudent::class, fn ($mail) => $mail->hasTo($beat->email));
	Mail::assertQueued(EventMessageExpert::class, fn ($mail) => $mail->hasTo($this->expert->email));
	Mail::assertQueuedCount(3);
});

it('sends no author copy unless asked', function () {
	seat($this->event);
	Mail::fake();

	app(PostMessage::class)->execute($this->event, $this->expert, 'Vorbereitung', '<p>x</p>');

	Mail::assertNotQueued(EventMessageExpert::class);
});

it('tells the office when a date reaches its minimum and when it fills up', function () {
	seat($this->event);
	Mail::fake();
	seat($this->event);

	Mail::assertQueued(ParticipantsChange::class, fn ($mail) => $mail->type === 'min' && $mail->hasTo('office@example.test'));

	Mail::fake();
	seat($this->event);

	Mail::assertQueued(ParticipantsChange::class, fn ($mail) => $mail->type === 'max');
});

it('tells the office when a date falls back below its minimum', function () {
	$anna = seat($this->event);
	seat($this->event);
	Mail::fake();

	app(CancelBooking::class)->execute($anna->bookings()->first(), BookingCancellationReason::Student);

	Mail::assertQueued(ParticipantsChange::class, fn ($mail) => $mail->type === 'belowMin');
});

it('writes legacy text, with a way into the dashboard', function () {
	expect((new ParticipantsChange($this->event->refresh(), 'min'))->render())
		->toContain('Min. Teilnehmerzahl erreicht – Rhino Einstiegskurs')
		->toContain('wurde erreicht.')
		->toContain('/dashboard/veranstaltung/'.$this->event->uuid.'/bearbeiten');
});
