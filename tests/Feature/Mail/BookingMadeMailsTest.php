<?php

declare(strict_types=1);

use App\Actions\Bookings\CreateBookingForUser;
use App\Enums\EventState;
use App\Mail\BookingCompleted;
use App\Mail\BookingCreatedInfo;
use App\Mail\EventConfirmationStudent;
use App\Mail\EventMessageStudent;
use App\Mail\RentalAddedInfoAdmin;
use App\Models\Course;
use App\Models\Event;
use App\Models\Location;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * The flow table's first row, *Booking made* ([[10-mail]]): who is told, with
 * what, and nobody else.
 */
beforeEach(function () {
	Mail::fake();
	Storage::fake('documents');
	config(['mail.admin' => 'office@example.test']);

	$this->student = User::factory()->student()->create(['first_name' => 'Anna', 'last_name' => 'Muster']);
	$this->expert = User::factory()->expert()->create(['first_name' => 'Kevin']);
	$location = Location::create(['description' => ['de' => 'Visualisierungs-Akademie, Zürich'], 'address' => ['de' => 'x'], 'publish' => true]);
	$this->event = Event::factory()
		->for(Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs'], 'fee' => '890.00']))
		->create(['location_id' => $location->id, 'max_participants' => 8, 'rentals_available' => 2]);
	$this->event->experts()->attach($this->expert);
	$this->event->dates()->create(['date' => now()->addMonth()->toDateString()]);
});

function book(Event $event, User $user, bool $rental = false)
{
	return app(CreateBookingForUser::class)->execute($event->refresh(), $user, $rental);
}

it('confirms the booking to the student and tells each expert and the office', function () {
	book($this->event, $this->student);

	Mail::assertQueued(BookingCompleted::class, fn ($mail) => $mail->hasTo($this->student->email));
	Mail::assertQueued(BookingCreatedInfo::class, fn ($mail) => $mail->hasTo($this->expert->email) && $mail->toExpert);
	Mail::assertQueued(BookingCreatedInfo::class, fn ($mail) => $mail->hasTo('office@example.test') && ! $mail->toExpert);
	Mail::assertQueuedCount(3);
});

it('tells the office about a laptop, and only when one was booked', function () {
	book($this->event, $this->student, rental: true);

	Mail::assertQueued(RentalAddedInfoAdmin::class, fn ($mail) => $mail->hasTo('office@example.test'));
});

it('sends nothing to an office that has no address', function () {
	config(['mail.admin' => null]);

	book($this->event, $this->student);

	Mail::assertNotQueued(BookingCreatedInfo::class, fn ($mail) => ! $mail->toExpert);
	Mail::assertQueuedCount(2);
});

it('sends the course confirmation with the invoice on a course already confirmed', function () {
	$this->event->update(['state' => EventState::Confirmed]);

	book($this->event, $this->student);

	Mail::assertQueued(EventConfirmationStudent::class, function ($mail) {
		return $mail->hasTo($this->student->email)
			&& $mail->invoice !== null
			&& Storage::disk('documents')->exists($mail->invoice->path())
			&& count($mail->attachments()) === 1;
	});
});

it('sends no course confirmation while the course is only planned', function () {
	book($this->event, $this->student);

	Mail::assertNotQueued(EventConfirmationStudent::class);
});

it('sends a late booker every earlier message, one mail each, and records it', function () {
	$first = Message::factory()->create(['event_id' => $this->event->id, 'user_id' => $this->expert->id, 'subject' => 'Vorbereitung']);
	$second = Message::factory()->create(['event_id' => $this->event->id, 'user_id' => $this->expert->id, 'subject' => 'Unterlagen']);

	book($this->event, $this->student);

	Mail::assertQueued(EventMessageStudent::class, 2);
	Mail::assertQueued(EventMessageStudent::class, fn ($mail) => $mail->post->is($first) && $mail->hasTo($this->student->email));
	expect($second->recipients()->whereKey($this->student->id)->exists())->toBeTrue();
});

it('writes legacy text, with this booking in it', function () {
	$booking = book($this->event, $this->student);

	$html = (new BookingCompleted($booking->refresh()))->render();

	expect($html)
		->toContain('Buchungsbestätigung – Rhino Einstiegskurs')
		->toContain('Guten Tag Anna Muster')
		->toContain($booking->number)
		->toContain('CHF 890')
		->toContain('Visualisierungs-Akademie, Zürich')
		->toContain('/de/student/profil');
});

it('renders every booking mail without an error', function () {
	$this->event->update(['state' => EventState::Confirmed]);
	$booking = book($this->event, $this->student, rental: true)->refresh();
	$post = Message::factory()->create(['event_id' => $this->event->id, 'user_id' => $this->expert->id, 'body' => '<p>Bitte <strong>Laptop</strong> mitbringen.</p>']);

	expect((new BookingCreatedInfo($booking, $this->expert, true))->render())->toContain('Hallo Kevin')->toContain('/de/experte/profil')
		->and((new RentalAddedInfoAdmin($booking))->render())->toContain('wurde ein Mietcomputer gebucht')
		->and((new EventConfirmationStudent($booking, null))->render())->toContain('Hiermit bestätigen wir die Durchführung')
		->and((new EventMessageStudent($post))->render())->toMatch('#<strong[^>]*>Laptop</strong>#'); // the theme inlines a style onto it
});
