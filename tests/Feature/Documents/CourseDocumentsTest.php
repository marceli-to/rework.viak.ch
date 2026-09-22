<?php

declare(strict_types=1);

use App\Actions\Documents\RenderParticipantList;
use App\Actions\Documents\RenderParticipationConfirmation;
use App\Enums\DocumentType;
use App\Enums\EventState;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Event;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Storage;

/**
 * The two course documents — the student's certificate and the expert's
 * participant list ([[08-accounts]]).
 */
beforeEach(function () {
	Country::query()->firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
	Storage::fake('documents');
});

function closedSeat(array $eventAttributes = []): Booking
{
	$event = Event::factory()->create([
		'date' => today()->subDays(30),
		'state' => EventState::Closed,
		'closed_at' => today()->subDays(29),
		...$eventAttributes,
	]);

	$event->dates()->create(['date' => today()->subDays(30), 'time_start' => '08:30:00', 'time_end' => '17:00:00']);

	$student = User::factory()->create([
		'first_name' => 'Antonia', 'last_name' => 'Haller',
		'city' => 'Orsières', 'country_code' => 'ch',
		'phone' => '+41 79 000 00 00',
	]);

	return Booking::factory()->create([
		'user_id' => $student->id,
		'event_id' => $event->id,
		'number' => '000326',
	]);
}

/*
|--------------------------------------------------------------------------
| The certificate
|--------------------------------------------------------------------------
*/

it('files a participation confirmation under the event date, not today', function () {
	$booking = closedSeat();

	$document = app(RenderParticipationConfirmation::class)->execute($booking);

	/*
	 * Legacy dates the document `closed_at` and builds the **filename** from
	 * `date('d-m-Y', time())`, so a certificate reissued later is filed under a
	 * date that appears nowhere on it. One date here.
	 */
	$closed = $booking->event->closed_at->format('d-m-Y');

	expect($document->type)->toBe(DocumentType::ParticipationConfirmation)
		->and($document->filename)->toBe("viak-teilnahmebestaetigung-{$closed}-000326.pdf")
		->and($document->date->format('d-m-Y'))->toBe($closed);

	Storage::disk('documents')->assertExists($document->path());
	expect(Storage::disk('documents')->get($document->path()))->toStartWith('%PDF-');
});

it('survives an event that was never closed', function () {
	// Legacy writes `date => $booking->event->closed_at` unchecked, which is how
	// a row acquires a null date.
	$booking = closedSeat(['closed_at' => null]);

	$document = app(RenderParticipationConfirmation::class)->execute($booking);

	expect($document->date->format('d-m-Y'))->toBe($booking->event->date->format('d-m-Y'));
});

it('replaces the student s certificate rather than filing a second one', function () {
	$booking = closedSeat();

	$first = app(RenderParticipationConfirmation::class)->execute($booking);
	$again = app(RenderParticipationConfirmation::class)->execute($booking);

	expect($again->id)->toBe($first->id)
		->and(UserDocument::query()->count())->toBe(1);
});

it('names the student and the booking on the certificate', function () {
	$booking = closedSeat();

	$html = view('documents.participation-confirmation', [
		'booking' => $booking->load(['event.course', 'event.dates', 'event.experts', 'user']),
	])->render();

	expect($html)->toContain('Teilnahmebestätigung')
		->and($html)->toContain('Antonia Haller')
		->and($html)->toContain('000326')
		->and($html)->toContain('erfolgreich absolviert hat');
});

/*
|--------------------------------------------------------------------------
| The participant list
|--------------------------------------------------------------------------
*/

it('lists the live seats with their contact details', function () {
	$booking = closedSeat();
	$event = $booking->event;

	$pdf = app(RenderParticipantList::class)->execute($event);

	expect($pdf)->toStartWith('%PDF-');

	$html = view('documents.participant-list', [
		'event' => $event->load(['course', 'experts']),
		'bookings' => $event->bookings()->active()->with('user')->get(),
	])->render();

	// The only document that carries these two, which is what made legacy's
	// missing ownership check matter (finding 5).
	expect($html)->toContain('+41 79 000 00 00')
		->and($html)->toContain($booking->user->email);
});

it('lists the cancelled seats when the course itself was called off', function () {
	$booking = closedSeat(['state' => EventState::Cancelled]);
	$booking->update(['cancelled_at' => now()]);

	$event = $booking->event->fresh();

	$html = view('documents.participant-list', [
		'event' => $event->load(['course', 'experts']),
		'bookings' => $event->bookings()->whereNotNull('cancelled_at')->with('user')->get(),
	])->render();

	// The live list would be empty and the expert would lose the list of people
	// they have to apologise to.
	expect($html)->toContain('Antonia Haller');
});

it('names the file after the course rather than a random string', function () {
	$event = closedSeat()->event;

	// Legacy's `viak-teilnehmerliste-{date}-{12 random}.pdf` is what makes its
	// 294 orphans impossible to reconcile with anything.
	expect(app(RenderParticipantList::class)->filename($event))
		->toBe('viak-teilnehmerliste-'.$event->number().'.pdf');
});

it('files nothing for a participant list', function () {
	$event = closedSeat()->event;

	app(RenderParticipantList::class)->execute($event);

	// It belongs to a course rather than to a customer, and it is out of date
	// the moment somebody cancels.
	expect(UserDocument::query()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Who may download it
|--------------------------------------------------------------------------
*/

it('gives the participant list only to the expert who teaches the course', function () {
	$booking = closedSeat();
	$event = $booking->event;

	$teaches = User::factory()->expert()->create(['email_verified_at' => now()]);
	$event->experts()->attach($teaches);

	$other = User::factory()->expert()->create(['email_verified_at' => now()]);

	$url = '/de/experte/profil/kurs/veranstaltung/'.$event->uuid.'/teilnehmerliste';

	$this->actingAs($teaches)->get($url)
		->assertOk()
		->assertHeader('content-type', 'application/pdf');

	// Legacy's route is `role:admin,expert` and this passes there (finding 5).
	$this->actingAs($other)->get($url)->assertNotFound();

	$this->actingAs(User::factory()->admin()->create(['email_verified_at' => now()]))
		->get($url)
		->assertOk();
});
