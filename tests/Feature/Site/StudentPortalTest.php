<?php

declare(strict_types=1);

use App\Enums\DocumentType;
use App\Enums\EventState;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Media;
use App\Models\Message;
use App\Models\User;
use App\Models\UserDocument;

/**
 * *Mein Profil* and the screens under it ([[08-accounts]], [[09-public-site]]).
 *
 * The four student-portal screens: the landing page with its profile block and
 * four lists, *Meine Dokumente*, one booked seat, and the invoice-address form.
 *
 * `08-accounts.md` built every endpoint these use and left the screens; what is
 * tested here is therefore mostly **which rows appear on which screen** and
 * **who is allowed to see them**, rather than the rules underneath, which
 * `Bookings/` and `Accounts/` already pin.
 */
function portalStudent(array $attributes = []): User
{
	return User::factory()->student()->create([
		'email_verified_at' => now(),
		'first_name' => 'Antonia',
		'last_name' => 'Haller',
		'street' => 'Kaiserstr.',
		'street_no' => '76',
		'zip' => '7752',
		'city' => 'Orsières',
		'country_code' => 'ch',
		...$attributes,
	]);
}

/** A seat on an event that is `$days` away — negative for one that has run. */
function portalSeat(User $user, int $days, array $eventAttributes = [], array $bookingAttributes = []): Booking
{
	$event = Event::factory()->create([
		'date' => today()->addDays($days),
		'state' => EventState::Confirmed,
		...$eventAttributes,
	]);

	return Booking::factory()->create([
		'user_id' => $user->id,
		'event_id' => $event->id,
		'course_fee' => 600,
		...$bookingAttributes,
	]);
}

beforeEach(function () {
	Country::query()->firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
	Country::query()->firstOrCreate(['code' => 'de'], ['name' => ['de' => 'Deutschland'], 'order' => 2]);
});

/*
|--------------------------------------------------------------------------
| Who gets in
|--------------------------------------------------------------------------
*/

it('is behind the same guards as the checkout', function () {
	$this->get('/de/student/profil')->assertRedirect('/login');

	$this->actingAs(User::factory()->admin()->create(['email_verified_at' => now()]))
		->get('/de/student/profil')
		->assertForbidden();

	$this->actingAs(User::factory()->expert()->create(['email_verified_at' => now()]))
		->get('/de/student/profil')
		->assertForbidden();
});

it('sends an unverified student to the verification notice', function () {
	$this->actingAs(portalStudent(['email_verified_at' => null]))
		->get('/de/student/profil')
		->assertRedirect('/email/verify');
});

it('serves every screen under the German segments, from config', function () {
	$user = portalStudent();
	$booking = portalSeat($user, 30);

	$this->actingAs($user)->get('/de/student/profil')->assertOk();
	$this->actingAs($user)->get('/de/student/profil/dokumente')->assertOk();
	$this->actingAs($user)->get('/de/student/profil/adresse/erstellen')->assertOk();
	$this->actingAs($user)
		->get('/de/student/profil/kurs/veranstaltung/'.$booking->event->uuid)
		->assertOk();
});

/*
|--------------------------------------------------------------------------
| Mein Profil
|--------------------------------------------------------------------------
*/

it('prints the account without asking the browser for it', function () {
	$this->actingAs(portalStudent(['company' => 'Nookla GmbH']))
		->get('/de/student/profil')
		->assertOk()
		->assertSee('Mein Profil')
		->assertSee('Nookla GmbH')
		->assertSee('Antonia Haller')
		->assertSee('Kaiserstr. 76')
		->assertSee('7752 Orsières');
});

it('offers logout as a POST, because a GET that ends a session is one a prefetcher can fire', function () {
	$this->actingAs(portalStudent())
		->get('/de/student/profil')
		->assertOk()
		->assertSee('action="'.route('logout').'"', escape: false)
		->assertSee('Logout');
});

/**
 * The split that is **not** legacy's.
 *
 * There it is two spatie flags, and `isConcluded` is set only for a booking
 * somebody already ticked as having attended — so 67 seats on courses that have
 * run are still listed as *Gebuchte Kurse* on the live site, across 63 students,
 * the oldest from March 2023 ([[StudentPortalController::splitBookings]]).
 */
it('splits the two course lists on the event’s date, not on a flag nobody ticked', function () {
    $user = portalStudent();

    $upcoming = portalSeat($user, 30);
    $past = portalSeat($user, -30);

    $response = $this->actingAs($user)->get('/de/student/profil')->assertOk();

    $html = $response->getContent();

    $booked = strpos($html, 'Gebuchte Kurse');
    $completed = strpos($html, 'Absolvierte Kurse');

    expect(strpos($html, $upcoming->event->course->getTranslation('title', 'de')))
        ->toBeGreaterThan($booked)
        ->toBeLessThan($completed);
});

it('counts today as upcoming, which legacy’s `date > today` made neither', function () {
	$user = portalStudent();
	portalSeat($user, 0);

	$this->actingAs($user)->get('/de/student/profil')
		->assertOk()
		->assertDontSee('Du hast noch keine Kurse gebucht.');
});

it('leaves a cancelled seat out of both lists', function () {
	$user = portalStudent();
	portalSeat($user, 30, [], ['cancelled_at' => now()]);

	$this->actingAs($user)->get('/de/student/profil')
		->assertOk()
		->assertSee('Du hast noch keine Kurse gebucht.')
		->assertSee('Du hast noch keine Kurse absolviert.');
});

it('offers a laptop only while the booking can still take one for free', function () {
	$user = portalStudent();
	portalSeat($user, 30, ['rentals_available' => 4]);

	$this->actingAs($user)->get('/de/student/profil')
		->assertOk()
		->assertSee('kannst Du bei uns einen Computer mieten', escape: false);
});

it('does not offer a laptop on an event that has none', function () {
	$user = portalStudent();
	portalSeat($user, 30, ['rentals_available' => 0]);

	$this->actingAs($user)->get('/de/student/profil')
		->assertOk()
		->assertDontSee('kannst Du bei uns einen Computer mieten', escape: false);
});

it('shows a booked laptop as its own line with its frozen price', function () {
	$user = portalStudent();
	portalSeat($user, 30, ['rentals_available' => 4], ['has_rental' => true, 'rental_fee' => 80]);

	$this->actingAs($user)->get('/de/student/profil')
		->assertOk()
		->assertSee('Mietcomputer')
		->assertSee('80.00');
});

/**
 * The figures the confirmation shows **before** the seat is given up — legacy
 * puts the same two in its `BookingResource` and builds its sentence out of
 * them. They come from [[CancellationPenalty]], the class that will raise the
 * invoice, so the dialog cannot promise one number and the bill say another.
 */
it('renders the cancellation penalty into the row, for the dialog to read', function () {
	$user = portalStudent();

	// Five days out: inside the 11-day window, so the whole net fee is due.
	portalSeat($user, 5);

	$this->actingAs($user)->get('/de/student/profil')
		->assertOk()
		->assertSee('\u0022penalty\u0022:true', escape: false)
		->assertSee('\u0022amount\u0022:\u0022600.00\u0022', escape: false)
		->assertSee('\u0022rate\u0022:100', escape: false);
});

it('reports no penalty outside the window', function () {
	$user = portalStudent();
	portalSeat($user, 60);

	$this->actingAs($user)->get('/de/student/profil')
		->assertOk()
		->assertSee('\u0022penalty\u0022:false', escape: false);
});

it('lists the saved invoice addresses and a way to add one', function () {
	$user = portalStudent();
	$user->addresses()->create([
		'first_name' => 'Anna',
		'last_name' => 'Muster',
		'company' => 'Muster AG',
		'street' => 'Bahnhofstrasse',
		'street_no' => '12',
		'zip' => '8001',
		'city' => 'Zürich',
		'country_code' => 'ch',
	]);

	$this->actingAs($user)->get('/de/student/profil')
		->assertOk()
		->assertSee('Rechnungsadressen')
		->assertSee('Muster AG, Anna Muster, Zürich')
		->assertSee('/de/student/profil/adresse/erstellen');
});

/*
|--------------------------------------------------------------------------
| The profile form
|--------------------------------------------------------------------------
*/

it('saves the account through the same Action the API uses', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil', [
			'first_name' => 'Antonia',
			'last_name' => 'Haller-Meier',
			'phone' => '079 000 00 00',
			'street' => 'Kaiserstr.',
			'street_no' => '76',
			'zip' => '7752',
			'city' => 'Orsières',
			'country_code' => 'ch',
		])
		->assertRedirect('/de/student/profil')
		->assertSessionHas('status');

	expect($user->refresh()->last_name)->toBe('Haller-Meier');
});

/**
 * The check legacy made nowhere, on any of its three role-specific copies of
 * this form: a session left open on a shared machine was enough to change the
 * address and then the password, and the owner had no way back
 * ([[UpdateProfile]]).
 */
it('will not change an email address without the current password', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil', [
			'first_name' => 'Antonia',
			'last_name' => 'Haller',
			'email' => 'somewhere-else@example.test',
		])
		->assertSessionHasErrors('current_password');

	expect($user->refresh()->email)->not->toBe('somewhere-else@example.test');
});

it('clears verification when the address changes, and says so', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil', [
			'first_name' => 'Antonia',
			'last_name' => 'Haller',
			'email' => 'somewhere-else@example.test',
			'current_password' => 'password',
		])
		->assertRedirect('/de/student/profil')
		->assertSessionHas('status', fn (string $status) => str_contains($status, 'bestätige'));

	expect($user->refresh()->email)->toBe('somewhere-else@example.test')
		->and($user->hasVerifiedEmail())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Meine Dokumente
|--------------------------------------------------------------------------
*/

it('lists a student’s own documents, behind the policy-gated download route', function () {
	$user = portalStudent();
	$booking = portalSeat($user, -30);

	$document = UserDocument::factory()->create([
		'user_id' => $user->id,
		'type' => DocumentType::ParticipationConfirmation,
		'date' => today()->subDays(30),
		'documentable_type' => Booking::class,
		'documentable_id' => $booking->id,
	]);

	$this->actingAs($user)->get('/de/student/profil/dokumente')
		->assertOk()
		->assertSee('Teilnahmebestätigung')
		->assertSee(route('documents.show', $document->uuid));
});

it('shows an invoice document with its number, total and status', function () {
	$user = portalStudent();

	$invoice = Invoice::factory()->create(['user_id' => $user->id, 'grand_total' => 649]);

	UserDocument::factory()->create([
		'user_id' => $user->id,
		'type' => DocumentType::Invoice,
		'date' => today(),
		'documentable_type' => Invoice::class,
		'documentable_id' => $invoice->id,
	]);

	$this->actingAs($user)->get('/de/student/profil/dokumente')
		->assertOk()
		->assertSee('Rechnung')
		->assertSee($invoice->number)
		->assertSee('649.00');
});

it('shows nobody else’s documents', function () {
	$mine = portalStudent();
	$theirs = portalStudent(['email' => 'someone@example.test']);

	UserDocument::factory()->create([
		'user_id' => $theirs->id,
		'type' => DocumentType::Invoice,
		'filename' => 'not-mine.pdf',
		'date' => today(),
	]);

	$this->actingAs($mine)->get('/de/student/profil/dokumente')
		->assertOk()
		->assertSee('Es sind noch keine Dokumente vorhanden.');
});

/*
|--------------------------------------------------------------------------
| One booked seat
|--------------------------------------------------------------------------
*/

it('404s the seat screen for an event the student has not booked', function () {
	$user = portalStudent();
	$other = Event::factory()->create(['date' => today()->addDays(30)]);

	$this->actingAs($user)
		->get('/de/student/profil/kurs/veranstaltung/'.$other->uuid)
		->assertNotFound();
});

it('shows the course notes and the materials to somebody on the course', function () {
	$user = portalStudent();
	$booking = portalSeat($user, 30);

	Message::factory()->create([
		'event_id' => $booking->event_id,
		'subject' => 'Anreise und Parkplätze',
		'body' => '<p>Bitte mit dem Zug.</p>',
	]);

	$file = Media::factory()->create([
		'mediable_type' => Event::class,
		'mediable_id' => $booking->event_id,
		'original_name' => 'Texturen.zip',
		'mime_type' => 'application/zip',
	]);

	$this->actingAs($user)
		->get('/de/student/profil/kurs/veranstaltung/'.$booking->event->uuid)
		->assertOk()
		->assertSee('Anreise und Parkplätze')
		->assertSee('Texturen.zip')
		->assertSee(route('media.download', $file->uuid));
});

/**
 * The relation that was missing: `port:media` filled 13 rows against `Event`
 * and nothing could read them, because the morph had no `media()` on the owning
 * side and an unread morph raises nothing at all ([[Event]]).
 */
it('reaches an event’s ported materials at all', function () {
	$event = Event::factory()->create();

	Media::factory()->create([
		'mediable_type' => Event::class,
		'mediable_id' => $event->id,
		'original_name' => 'Modelle.zip',
	]);

	expect($event->refresh()->media)->toHaveCount(1);
});

it('keeps a cancelled seat readable but shuts the thread', function () {
	$user = portalStudent();
	$booking = portalSeat($user, 30, [], ['cancelled_at' => now()]);

	Message::factory()->create([
		'event_id' => $booking->event_id,
		'subject' => 'Anreise und Parkplätze',
	]);

	$this->actingAs($user)
		->get('/de/student/profil/kurs/veranstaltung/'.$booking->event->uuid)
		->assertOk()
		->assertSee('annulliert')
		->assertDontSee('Anreise und Parkplätze');
});

/*
|--------------------------------------------------------------------------
| Invoice addresses
|--------------------------------------------------------------------------
*/

it('creates an invoice address and comes back to the profile', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil/adresse', [
			'first_name' => 'Anna',
			'last_name' => 'Muster',
			'company' => 'Muster AG',
			'street' => 'Bahnhofstrasse',
			'street_no' => '12',
			'zip' => '8001',
			'city' => 'Zürich',
			'country_code' => 'ch',
		])
		->assertRedirect('/de/student/profil')
		->assertSessionHas('status');

	expect($user->addresses()->count())->toBe(1);
});

it('soft-deletes an address rather than losing where an invoice was sent', function () {
	$user = portalStudent();
	$address = $user->addresses()->create([
		'first_name' => 'Anna',
		'last_name' => 'Muster',
		'street' => 'Bahnhofstrasse',
		'zip' => '8001',
		'city' => 'Zürich',
		'country_code' => 'ch',
	]);

	$this->actingAs($user)
		->delete('/de/student/profil/adresse/'.$address->uuid)
		->assertRedirect('/de/student/profil');

	expect($user->addresses()->count())->toBe(0)
		->and($address->fresh()->trashed())->toBeTrue();
});

/**
 * Object-level ownership, which is where legacy is thin: **nine**
 * `$this->authorize()` calls in the whole application, and all 28 of its
 * FormRequests return `authorize() => true` ([[08-accounts]]).
 */
it('will not let one student edit or delete another’s address', function () {
	$mine = portalStudent();
	$theirs = portalStudent(['email' => 'someone@example.test']);

	$address = $theirs->addresses()->create([
		'first_name' => 'Anna',
		'last_name' => 'Muster',
		'street' => 'Bahnhofstrasse',
		'zip' => '8001',
		'city' => 'Zürich',
		'country_code' => 'ch',
	]);

	$this->actingAs($mine)
		->get('/de/student/profil/adresse/bearbeiten/'.$address->uuid)
		->assertForbidden();

	$this->actingAs($mine)
		->delete('/de/student/profil/adresse/'.$address->uuid)
		->assertForbidden();

	expect($address->fresh()->trashed())->toBeFalse();
});

/**
 * The form prints the current address in its E-Mail field, as an edit form
 * does — so *filled* is not *changed*, and treating the two as one made the
 * screen unusable: every save demanded the password.
 *
 * Legacy sidesteps it by calling the field `new_email` and leaving it blank.
 * Here the rule says what it means instead ([[UpdateProfileRequest::changesEmail]]).
 */
it('saves with the email resent unchanged, and asks for no password', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil', [
			'first_name' => 'Antonia',
			'last_name' => 'Haller',
			'company' => 'Nookla GmbH',
			'email' => $user->email,
		])
		->assertRedirect('/de/student/profil')
		->assertSessionHasNoErrors();

	expect($user->refresh()->company)->toBe('Nookla GmbH')
		->and($user->hasVerifiedEmail())->toBeTrue();
});

it('names the field in German when the password is missing', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil', [
			'first_name' => 'Antonia',
			'last_name' => 'Haller',
			'email' => 'somewhere-else@example.test',
		])
		->assertSessionHasErrors(['current_password' => 'Aktuelles Passwort muss ausgefüllt sein.']);
});

/**
 * The form posts every field, so an untouched password box arrives as `null`
 * (`ConvertEmptyStringsToNull`) rather than not arriving at all. An API client
 * omits the key and never meets this; the screen does, on every save.
 */
it('accepts an empty current-password box when nothing needs confirming', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil', [
			'first_name' => 'Antonia',
			'last_name' => 'Haller',
			'company' => 'Nookla GmbH',
			'email' => $user->email,
			'password' => null,
			'password_confirmation' => null,
			'current_password' => null,
		])
		->assertRedirect('/de/student/profil')
		->assertSessionHasNoErrors();

	expect($user->refresh()->company)->toBe('Nookla GmbH');
});
