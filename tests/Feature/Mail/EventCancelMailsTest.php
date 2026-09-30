<?php

declare(strict_types=1);

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Events\SetEventState;
use App\Enums\BookingCancellationReason;
use App\Enums\EventState;
use App\Mail\EventCancelCustomer;
use App\Mail\EventCancelExpert;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * *Event cancelled* ([[10-mail]]): *Kursabsage* to each student who held a
 * seat and to each expert; nobody who had already left.
 */
beforeEach(function () {
	$this->course = Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs'], 'fee' => '890.00']);
	$this->event = Event::factory()->for($this->course)->create(['date' => now()->addDays(20)->toDateString()]);
	$this->event->dates()->create(['date' => now()->addDays(20)->toDateString()]);
	$this->expert = User::factory()->expert()->create();
	$this->event->experts()->attach($this->expert);

	$this->anna = User::factory()->create();
	$this->left = User::factory()->create();
	app(CreateBookingForUser::class)->execute($this->event->refresh(), $this->anna);
	app(CreateBookingForUser::class)->execute($this->event->refresh(), $this->left);
	app(CancelBooking::class)->execute($this->left->bookings()->first(), BookingCancellationReason::Student);

	Mail::fake();
});

it('tells each student who held a seat, and each expert', function () {
	app(SetEventState::class)->execute($this->event->refresh(), EventState::Cancelled);

	Mail::assertQueued(EventCancelCustomer::class, 1);
	Mail::assertQueued(EventCancelCustomer::class, fn ($mail) => $mail->hasTo($this->anna->email));
	Mail::assertQueued(EventCancelExpert::class, fn ($mail) => $mail->hasTo($this->expert->email));
});

it('offers the course next two published dates', function () {
	foreach ([40, 60, 80] as $days) {
		$later = Event::factory()->for($this->course)->create(['date' => now()->addDays($days)->toDateString()]);
		$later->dates()->create(['date' => now()->addDays($days)->toDateString()]);
	}
	Event::factory()->for($this->course)->create(['date' => now()->addDays(30)->toDateString(), 'publish' => false]);

	app(SetEventState::class)->execute($this->event->refresh(), EventState::Cancelled);

	Mail::assertQueued(EventCancelCustomer::class, function ($mail) {
		$html = $mail->render();

		return str_contains($html, now()->addDays(40)->format('d.m.Y'))
			&& str_contains($html, now()->addDays(60)->format('d.m.Y'))
			&& ! str_contains($html, now()->addDays(80)->format('d.m.Y'))
			&& ! str_contains($html, now()->addDays(30)->format('d.m.Y'));
	});
});

it('says a new date will follow when there is none', function () {
	app(SetEventState::class)->execute($this->event->refresh(), EventState::Cancelled);

	Mail::assertQueued(EventCancelCustomer::class, fn ($mail) => str_contains($mail->render(), 'Falls der Kurs an einem neuen Datum stattfinden wird'));
});
