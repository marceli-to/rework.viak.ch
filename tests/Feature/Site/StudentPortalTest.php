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

it('lists the saved invoice addresses and a way to add one, on the edit screen', function () {
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

	$this->actingAs($user)->get('/de/student/profil/bearbeiten')
		->assertOk()
		->assertSee('Rechnungsadressen')
		->assertSee('Muster AG, Anna Muster, Zürich')
		->assertSee('/de/student/profil/adresse/erstellen');
});

/*
|--------------------------------------------------------------------------
| The form is a screen, not a panel
|--------------------------------------------------------------------------
*/

/**
 * Marcel's call, 2026-09-22. Legacy toggles the form in place off `isEdit` —
 * component state — and *Rechnungsadressen* lives inside it with links to
 * screens of their own. So adding an address landed you back on a shut panel
 * with the new address invisible in it; legacy has the same hole and an SPA
 * hides it ([[SiteUrl::studentProfileEdit]]).
 */
it('shows the address block on the profile and the form on its own URL', function () {
	$user = portalStudent(['company' => 'Nookla GmbH']);

	$this->actingAs($user)->get('/de/student/profil')
		->assertOk()
		->assertSee('Nookla GmbH')
		->assertSee('/de/student/profil/bearbeiten')
		->assertDontSee('Rechnungsadressen')
		->assertDontSee('Zugangsdaten');

	$this->actingAs($user)->get('/de/student/profil/bearbeiten')
		->assertOk()
		->assertSee('Profil bearbeiten')
		->assertSee('Rechnungsadressen')
		->assertSee('Zugangsdaten');
});

/**
 * **The form and nothing else** (Marcel, 2026-09-22) — a sibling of the address
 * screens rather than a state of the profile. Legacy keeps the whole page
 * around the form, because there it is one page and a toggle.
 */
it('puts nothing but the form on the edit screen', function () {
	$user = portalStudent();
	portalSeat($user, 30);

	$this->actingAs($user)->get('/de/student/profil/bearbeiten')
		->assertOk()
		->assertDontSee('Merkliste')
		->assertDontSee('Gebuchte Kurse')
		->assertDontSee('Absolvierte Kurse');
});

/**
 * And *Zurück* where the profile has *Logout* — the same control the address
 * screens carry. Offering to end the session from the middle of an unsaved form
 * is not the exit anyone is looking for.
 */
it('gives the edit screen a Zurück rather than the profile’s Logout', function () {
	$user = portalStudent();

	$this->actingAs($user)->get('/de/student/profil/bearbeiten')
		->assertOk()
		->assertSee('Zurück')
		->assertSee('href="/de/student/profil"', escape: false)
		->assertDontSee('Logout');

	$this->actingAs($user)->get('/de/student/profil')
		->assertOk()
		->assertSee('Logout');
});

it('keeps the four lists on the profile', function () {
	$user = portalStudent();
	portalSeat($user, 30);

	$this->actingAs($user)->get('/de/student/profil')
		->assertOk()
		->assertSee('Merkliste')
		->assertSee('Gebuchte Kurse')
		->assertSee('Absolvierte Kurse')
		->assertSee('Dokumente');
});

/**
 * The whole point of giving the form a URL: the way out and the way back line
 * up, so the address you just added is in a list you can see.
 */
it('sends the address screens back into the form, not to the closed profile', function () {
	$user = portalStudent();
	$address = $user->addresses()->create([
		'first_name' => 'Anna',
		'last_name' => 'Muster',
		'street' => 'Bahnhofstrasse',
		'zip' => '8001',
		'city' => 'Zürich',
		'country_code' => 'ch',
	]);

	// The back link on both address screens.
	$this->actingAs($user)->get('/de/student/profil/adresse/erstellen')
		->assertOk()
		->assertSee('href="/de/student/profil/bearbeiten"', escape: false);

	$this->actingAs($user)->get('/de/student/profil/adresse/bearbeiten/'.$address->uuid)
		->assertOk()
		->assertSee('href="/de/student/profil/bearbeiten"', escape: false);
});

it('renders the profile screens without any JavaScript of their own', function () {
	$user = portalStudent();

	// No toggle, no `x-show`, no `x-cloak` on the profile column — the pencil
	// and *Abbrechen* are links. What Alpine is left on the page belongs to the
	// collapsibles, the basket and the cancellation dialogs.
	$html = $this->actingAs($user)->get('/de/student/profil/bearbeiten')->assertOk()->getContent();

	expect($html)->not->toContain('editing');
});

/*
|--------------------------------------------------------------------------
| The profile form
|--------------------------------------------------------------------------
*/

it('saves the account through the same Action the API uses', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil/bearbeiten', [
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
		->post('/de/student/profil/bearbeiten', [
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
		->post('/de/student/profil/bearbeiten', [
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

it('creates an invoice address and comes back into the form', function () {
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
		// Into the form, where the list it belongs to is — not to the read view,
		// which shows no addresses at all.
		->assertRedirect('/de/student/profil/bearbeiten')
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
		->assertRedirect('/de/student/profil/bearbeiten');

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
		->post('/de/student/profil/bearbeiten', [
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
		->post('/de/student/profil/bearbeiten', [
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
		->post('/de/student/profil/bearbeiten', [
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

/*
|--------------------------------------------------------------------------
| An invoice address is a person or a firm
|--------------------------------------------------------------------------
*/

/**
 * Marcel, 2026-09-22. The first cut required both names and left the company
 * optional, which is legacy's shape — and *Rechnungen, Muster AG* needs no
 * contact name ([[StoreAddressRequest]]).
 */
it('accepts an invoice address with a company and no name', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil/adresse', [
			'company' => 'Muster AG',
			'street' => 'Bahnhofstrasse',
			'zip' => '8001',
			'city' => 'Zürich',
			'country_code' => 'ch',
		])
		->assertRedirect('/de/student/profil/bearbeiten')
		->assertSessionHasNoErrors();

	expect($user->addresses()->count())->toBe(1);
});

it('accepts one with a name and no company, as before', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil/adresse', [
			'first_name' => 'Anna',
			'last_name' => 'Muster',
			'street' => 'Bahnhofstrasse',
			'zip' => '8001',
			'city' => 'Zürich',
			'country_code' => 'ch',
		])
		->assertSessionHasNoErrors();

	expect($user->addresses()->count())->toBe(1);
});

it('refuses half a name with no company, because half a name is not one', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil/adresse', [
			'first_name' => 'Anna',
			'street' => 'Bahnhofstrasse',
			'zip' => '8001',
			'city' => 'Zürich',
			'country_code' => 'ch',
		])
		->assertSessionHasErrors(['last_name' => 'Bitte Vor- und Nachname oder eine Firma angeben.']);

	expect($user->addresses()->count())->toBe(0);
});

it('refuses neither, and says the rule rather than the branch that fired', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil/adresse', [
			'street' => 'Bahnhofstrasse',
			'zip' => '8001',
			'city' => 'Zürich',
			'country_code' => 'ch',
		])
		->assertSessionHasErrors(['company' => 'Bitte Vor- und Nachname oder eine Firma angeben.']);
});

/*
|--------------------------------------------------------------------------
| The two form details the screenshots caught
|--------------------------------------------------------------------------
*/

/**
 * `form/_global.scss` sets the input colour twice and the **second** rule wins,
 * so production's values are teal and its labels black. Read from the
 * stylesheet the first rule looks like the answer, which is how this shipped
 * inverted on every form on the site ([[x-site.field]]).
 */
it('paints form values teal, as production does', function () {
	$html = $this->get('/de/registration')->assertOk()->getContent();

	expect($html)->toContain('font-bold text-teal');
});

/**
 * *Abbrechen* is `.form-helper` — 14/16/18 and italic — where it had no size at
 * all and inherited the page's 24px, coming out larger than the button above
 * it. The gap is the button's `.form-group` margin, 16 and 32 from `lg`.
 */
it('sizes Abbrechen below the button rather than above it', function () {
	$html = $this->actingAs(portalStudent())
		->get('/de/student/profil/bearbeiten')
		->assertOk()
		->getContent();

	expect($html)
		->toContain('inline-block text-md italic transition-colors hover:text-teal sm:text-lg lg:text-xl')
		->toContain('<div class="mb-16 lg:mb-32">');
});

/*
|--------------------------------------------------------------------------
| The alert box
|--------------------------------------------------------------------------
*/

/**
 * `.form-danger-zone` is `border: 2px solid` — **all four sides** — and it sets
 * its own 14/16/18 type. This had `border-y-2` and no size, which is what comes
 * of reading `borderTopWidth`/`borderBottomWidth` and calling it measured.
 */
it('draws the delete box as a box, at its own size', function () {
	$user = portalStudent();
	$address = $user->addresses()->create([
		'first_name' => 'Anna', 'last_name' => 'Muster',
		'street' => 'Bahnhofstrasse', 'zip' => '8001', 'city' => 'Zürich', 'country_code' => 'ch',
	]);

	$this->actingAs($user)
		->get('/de/student/profil/adresse/bearbeiten/'.$address->uuid)
		->assertOk()
		->assertSee('border-2 border-danger', escape: false)
		->assertDontSee('border-y-2', escape: false)
		->assertSee('text-md text-danger', escape: false);
});

/** `Form.vue`'s own `title()`, which is neither *Rechnungsadresse* nor *erfassen*. */
it('titles the address screens the way legacy does', function () {
	$user = portalStudent();
	$address = $user->addresses()->create([
		'first_name' => 'Anna', 'last_name' => 'Muster',
		'street' => 'Bahnhofstrasse', 'zip' => '8001', 'city' => 'Zürich', 'country_code' => 'ch',
	]);

	$this->actingAs($user)->get('/de/student/profil/adresse/erstellen')
		->assertOk()
		->assertSee('Adresse hinzufügen')
		->assertDontSee('Rechnungsadresse hinzufügen');

	$this->actingAs($user)->get('/de/student/profil/adresse/bearbeiten/'.$address->uuid)
		->assertOk()
		->assertSee('Adresse bearbeiten');
});

/**
 * The site addresses the customer informally and **capitalises** it — 90
 * occurrences in legacy's copy and not one lowercase. This sentence had it both
 * ways inside itself.
 */
it('capitalises Dir and Deine in the verification notice', function () {
	$user = portalStudent();

	$this->actingAs($user)
		->post('/de/student/profil/bearbeiten', [
			'first_name' => 'Antonia',
			'last_name' => 'Haller',
			'email' => 'somewhere-else@example.test',
			'current_password' => 'password',
		])
		->assertSessionHas('status', fn (string $status) => str_contains($status, 'Deine neue E-Mail-Adresse')
			&& str_contains($status, 'wir Dir geschickt')
			&& ! str_contains($status, 'deine')
			&& ! str_contains($status, ' dir '));
});

/**
 * `Index.vue` puts `md:mt-3x` on `index == 0` and nothing on the rest, so the
 * first address sits **12px** under the heading at desktop against the 32 every
 * other row keeps. A desktop-only rule: below `bp-md` the first row keeps the
 * row margin like any other, which is why it is `lg:mt-12` and not a margin
 * removed.
 */
it('pulls the first invoice address up at desktop, and only there', function () {
	$user = portalStudent();

	foreach ([['Anna', 'Muster'], ['Beat', 'Keller']] as [$first, $last]) {
		$user->addresses()->create([
			'first_name' => $first, 'last_name' => $last,
			'street' => 'Bahnhofstrasse', 'zip' => '8001', 'city' => 'Zürich', 'country_code' => 'ch',
		]);
	}

	$html = $this->actingAs($user)->get('/de/student/profil/bearbeiten')->assertOk()->getContent();

	// One row carries it, the other does not.
	expect(substr_count($html, 'lg:mt-12'))->toBe(1);
});
