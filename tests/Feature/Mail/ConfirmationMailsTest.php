<?php

declare(strict_types=1);

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Events\SetEventState;
use App\Enums\BookingCancellationReason;
use App\Enums\EventState;
use App\Mail\EventConfirmationCustomer;
use App\Mail\EventConfirmationExpert;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * *Event confirmed* ([[10-mail]]): each student holding a seat gets the course
 * confirmation with their invoice, each expert theirs.
 */
beforeEach(function () {
	Storage::fake('documents');
	$this->event = Event::factory()->for(Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs'], 'fee' => '890.00']))->create();
	$this->event->dates()->create(['date' => now()->addMonth()->toDateString()]);
	$this->expert = User::factory()->expert()->create(['first_name' => 'Kevin']);
	$this->event->experts()->attach($this->expert);

	$this->anna = User::factory()->create();
	$this->beat = User::factory()->create();
	$gone = User::factory()->create();
	foreach ([$this->anna, $this->beat, $gone] as $student) {
		app(CreateBookingForUser::class)->execute($this->event->refresh(), $student);
	}
	app(CancelBooking::class)->execute($gone->bookings()->first(), BookingCancellationReason::Student);

	Mail::fake();
});

it('sends each booked student the confirmation with their own invoice, and each expert theirs', function () {
	app(SetEventState::class)->execute($this->event->refresh(), EventState::Confirmed);

	Mail::assertQueued(EventConfirmationCustomer::class, 2);
	foreach ([$this->anna, $this->beat] as $student) {
		Mail::assertQueued(EventConfirmationCustomer::class, fn ($mail) => $mail->hasTo($student->email)
			&& $mail->invoice?->user_id === $student->id
			&& Storage::disk('documents')->exists($mail->invoice->path()));
	}
	Mail::assertQueued(EventConfirmationExpert::class, fn ($mail) => $mail->hasTo($this->expert->email));
});

it('does not confirm twice when a confirmed course is saved again', function () {
	app(SetEventState::class)->execute($this->event->refresh(), EventState::Confirmed);
	Mail::fake();

	app(SetEventState::class)->execute($this->event->refresh(), EventState::Confirmed);

	Mail::assertNothingQueued();
});

it('writes legacy text to the expert', function () {
	expect((new EventConfirmationExpert($this->event->refresh(), $this->expert))->render())
		->toContain('Sali Kevin')
		->toContain('Hiermit bestätigen wir die Durchführung des oben erwähnten Kurses')
		->toContain('/de/experte/profil/kurs/veranstaltung/'.$this->event->uuid);
});

it('confirms a seat that cannot be billed without an invoice, and the rest with theirs', function () {
	$odd = User::factory()->create();
	Booking::factory()->for($this->event)->withRental()->create(['user_id' => $odd->id, 'rental_fee' => '0.00']);

	app(SetEventState::class)->execute($this->event->refresh(), EventState::Confirmed);

	Mail::assertQueued(EventConfirmationCustomer::class, 3);
	Mail::assertQueued(EventConfirmationCustomer::class, fn ($mail) => $mail->hasTo($odd->email) && $mail->invoice === null);
	Mail::assertQueued(EventConfirmationCustomer::class, fn ($mail) => $mail->hasTo($this->anna->email) && $mail->invoice !== null);
});
