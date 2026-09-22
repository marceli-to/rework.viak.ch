<?php

declare(strict_types=1);

use App\Enums\EventState;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Event;
use App\Models\Media;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The expert portal — `/de/experte/profil` and the four screens under it
 * ([[08-accounts]], [[09-public-site]]).
 *
 * Most of what is tested here is **who may see what**, because that is what
 * separates this portal from legacy's: there every screen is gated by
 * `role:admin,expert` and nothing else, so any of the 18 accounts holding the
 * role can open any course in the archive, read its thread, add files to it and
 * download the contact details of everyone on it (findings 4 and 5).
 */
function expertUser(array $attributes = []): User
{
	return User::factory()->expert()->create([
		'email_verified_at' => now(),
		'first_name' => 'Jonas',
		'last_name' => 'Brand',
		'city' => 'Zürich',
		'country_code' => 'ch',
		...$attributes,
	]);
}

/** An event `$days` away that `$expert` teaches — negative for one that has run. */
function taughtEvent(User $expert, int $days = 14, array $attributes = []): Event
{
	$event = Event::factory()->create([
		'date' => today()->addDays($days),
		'state' => EventState::Confirmed,
		...$attributes,
	]);

	$event->experts()->attach($expert);

	return $event;
}

/** A student holding a seat on it. */
function expertSeatOn(Event $event, array $userAttributes = []): Booking
{
	$student = User::factory()->student()->create([
		'email_verified_at' => now(),
		'city' => 'Winterthur',
		...$userAttributes,
	]);

	return Booking::factory()->create([
		'user_id' => $student->id,
		'event_id' => $event->id,
		'course_fee' => 600,
	]);
}

beforeEach(function () {
	Country::query()->firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
	Storage::fake('public');
});

/*
|--------------------------------------------------------------------------
| Who gets in
|--------------------------------------------------------------------------
*/

it('is behind auth, verification and the expert role', function () {
	$this->get('/de/experte/profil')->assertRedirect('/login');

	$this->actingAs(User::factory()->student()->create(['email_verified_at' => now()]))
		->get('/de/experte/profil')
		->assertForbidden();
});

it('lets an admin in, which is legacy s own guard', function () {
	$this->actingAs(User::factory()->admin()->create(['email_verified_at' => now()]))
		->get('/de/experte/profil')
		->assertOk();
});

it('sends an unverified expert to the verification notice', function () {
	$this->actingAs(expertUser(['email_verified_at' => null]))
		->get('/de/experte/profil')
		->assertRedirect('/email/verify');
});

/*
|--------------------------------------------------------------------------
| The landing screen
|--------------------------------------------------------------------------
*/

it('lists the courses taught, split on the date', function () {
	$expert = expertUser();
	$upcoming = taughtEvent($expert, 14);
	$past = taughtEvent($expert, -30);

	$this->actingAs($expert)->get('/de/experte/profil')
		->assertOk()
		->assertSee('Bevorstehende Kurse')
		->assertSee('Vergangene Kurse')
		->assertSee($upcoming->course->getTranslation('title', 'de'))
		->assertSee($past->course->getTranslation('title', 'de'));
});

it('counts today as upcoming, the boundary the student lists use', function () {
	$expert = expertUser();
	$event = taughtEvent($expert, 0);

	$response = $this->actingAs($expert)->get('/de/experte/profil');

	// The course appears once. What the test pins is that it is in the *first*
	// list — legacy's `date > today` put an event happening today in neither.
	$html = $response->getContent();
	$title = $event->course->getTranslation('title', 'de');

	expect(strpos($html, $title))->toBeLessThan(strpos($html, 'Vergangene Kurse'));
});

it('does not list a course somebody else teaches', function () {
	$expert = expertUser();
	$other = taughtEvent(expertUser(['email' => 'other@viak.test']), 14);

	$this->actingAs($expert)->get('/de/experte/profil')
		->assertOk()
		->assertDontSee($other->course->getTranslation('title', 'de'))
		->assertSee('Du hast keine bevorstehenden Kurse.');
});

it('shows the seats taken against the maximum, and not the fee', function () {
	$expert = expertUser();
	$event = taughtEvent($expert, 14, ['max_participants' => 8, 'fee' => 640]);
	expertSeatOn($event);
	expertSeatOn($event);

	$this->actingAs($expert)->get('/de/experte/profil')
		->assertOk()
		->assertSee('Teilnehmer')
		->assertSeeText('2 / 8 Teilnehmer')
		// `showFee="false"` — what the course costs is the student's question.
		->assertDontSee('640.00');
});

it('does not count a cancelled seat towards the total', function () {
	$expert = expertUser();
	$event = taughtEvent($expert, 14, ['max_participants' => 8]);
	expertSeatOn($event);
	expertSeatOn($event)->update(['cancelled_at' => now()]);

	$this->actingAs($expert)->get('/de/experte/profil')
		->assertOk()
		->assertSeeText('1 / 8 Teilnehmer');
});

/*
|--------------------------------------------------------------------------
| The profile form
|--------------------------------------------------------------------------
*/

it('edits the profile on a screen of its own, without the address block', function () {
	$this->actingAs(expertUser())->get('/de/experte/profil/bearbeiten')
		->assertOk()
		->assertSee('Profil bearbeiten')
		->assertSee('Zugangsdaten')
		// An expert is not a customer and holds no invoice addresses.
		->assertDontSee('Rechnungsadressen');
});

it('saves a change without asking for the password', function () {
	$expert = expertUser();

	$this->actingAs($expert)->post('/de/experte/profil/bearbeiten', [
		'first_name' => 'Jonas',
		'last_name' => 'Brand',
		'phone' => '+41 44 123 45 67',
		// The form prints the current address, so it comes back unchanged —
		// which is not a credential change ([[UpdateProfileRequest]]).
		'email' => $expert->email,
		'current_password' => '',
	])->assertRedirect('/de/experte/profil');

	expect($expert->fresh()->phone)->toBe('+41 44 123 45 67');
});

it('wants the current password to change the address, and clears verification', function () {
	$expert = expertUser(['password' => bcrypt('old-password-123')]);

	$this->actingAs($expert)->post('/de/experte/profil/bearbeiten', [
		'first_name' => 'Jonas',
		'last_name' => 'Brand',
		'email' => 'moved@viak.test',
	])->assertSessionHasErrors('current_password');

	$this->actingAs($expert)->post('/de/experte/profil/bearbeiten', [
		'first_name' => 'Jonas',
		'last_name' => 'Brand',
		'email' => 'moved@viak.test',
		'current_password' => 'old-password-123',
	])->assertRedirect('/de/experte/profil');

	expect($expert->fresh()->email)->toBe('moved@viak.test')
		->and($expert->fresh()->hasVerifiedEmail())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| One course
|--------------------------------------------------------------------------
*/

it('opens a course it teaches', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	$booking = expertSeatOn($event, ['first_name' => 'Antonia', 'last_name' => 'Haller']);

	$this->actingAs($expert)->get('/de/experte/profil/kurs/veranstaltung/'.$event->uuid)
		->assertOk()
		->assertSee('Informationen')
		->assertSee('Teilnehmer')
		->assertSee('Antonia Haller')
		->assertSee('Winterthur');
});

it('404s on a course somebody else teaches — finding 5', function () {
	$expert = expertUser();
	$event = taughtEvent(expertUser(['email' => 'other@viak.test']));
	expertSeatOn($event, ['first_name' => 'Antonia', 'last_name' => 'Haller']);

	// **A 404 rather than a 403**: answering *forbidden* would confirm that the
	// uuid is a real course.
	$this->actingAs($expert)->get('/de/experte/profil/kurs/veranstaltung/'.$event->uuid)
		->assertNotFound();
});

it('lets an admin open any course', function () {
	$event = taughtEvent(expertUser());

	$this->actingAs(User::factory()->admin()->create(['email_verified_at' => now()]))
		->get('/de/experte/profil/kurs/veranstaltung/'.$event->uuid)
		->assertOk();
});

it('shows the cancelled seats where the course itself was called off', function () {
	$expert = expertUser();
	$event = taughtEvent($expert, 14, ['state' => EventState::Cancelled]);
	expertSeatOn($event, ['first_name' => 'Antonia', 'last_name' => 'Haller'])
		->update(['cancelled_at' => now()]);

	$this->actingAs($expert)->get('/de/experte/profil/kurs/veranstaltung/'.$event->uuid)
		->assertOk()
		// The live list would be empty, and the expert would lose the list of
		// people they have to apologise to.
		->assertSee('Antonia Haller')
		->assertSee('annulliert');
});

it('hides a cancelled seat on a live course', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	expertSeatOn($event, ['first_name' => 'Antonia', 'last_name' => 'Haller'])
		->update(['cancelled_at' => now()]);

	$this->actingAs($expert)->get('/de/experte/profil/kurs/veranstaltung/'.$event->uuid)
		->assertOk()
		->assertDontSee('Antonia Haller')
		->assertSee('Es sind keine Anmeldungen für diesen Kurs vorhanden.');
});

it('prefers the firm on the profile over the one on the invoice address', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	$booking = expertSeatOn($event, ['company' => 'Muster AG']);
	$booking->user->addresses()->create([
		'first_name' => 'Anna', 'last_name' => 'Muster', 'company' => 'Andere GmbH',
		'street' => 'Bahnhofstrasse', 'street_no' => '1',
		'zip' => '8001', 'city' => 'Zürich', 'country_code' => 'ch',
	]);

	$this->actingAs($expert)->get('/de/experte/profil/kurs/veranstaltung/'.$event->uuid)
		->assertOk()
		->assertSee('Muster AG')
		->assertDontSee('Andere GmbH');
});

it('falls back to the invoice address firm', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	$booking = expertSeatOn($event, ['company' => null]);
	$booking->user->addresses()->create([
		'first_name' => 'Anna', 'last_name' => 'Muster', 'company' => 'Andere GmbH',
		'street' => 'Bahnhofstrasse', 'street_no' => '1',
		'zip' => '8001', 'city' => 'Zürich', 'country_code' => 'ch',
	]);

	$this->actingAs($expert)->get('/de/experte/profil/kurs/veranstaltung/'.$event->uuid)
		->assertOk()
		->assertSee('Andere GmbH');
});

it('does not put a participant s email address on the screen', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	$booking = expertSeatOn($event, ['email' => 'antonia@example.test', 'phone' => '+41 79 000 00 00']);

	$this->actingAs($expert)->get('/de/experte/profil/kurs/veranstaltung/'.$event->uuid)
		->assertOk()
		->assertDontSee('antonia@example.test')
		->assertDontSee('+41 79 000 00 00');
});

/*
|--------------------------------------------------------------------------
| The thread
|--------------------------------------------------------------------------
*/

it('shows the thread as a row and an Anzeigen', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	expertSeatOn($event);
	Message::factory()->create([
		'event_id' => $event->id,
		'user_id' => $expert->id,
		'subject' => 'Anreise und Parkplätze',
		'body' => '<p>Der Parkplatz hinter dem Gebäude ist reserviert.</p>',
	]);

	$this->actingAs($expert)->get('/de/experte/profil/kurs/veranstaltung/'.$event->uuid)
		->assertOk()
		->assertSee('Anreise und Parkplätze')
		->assertSee('Anzeigen')
		->assertSee('Jonas Brand');
});

it('posts a note and records who it reaches', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	$first = expertSeatOn($event);
	$second = expertSeatOn($event);
	// A cancelled seat is not a recipient.
	expertSeatOn($event)->update(['cancelled_at' => now()]);

	$this->actingAs($expert)->post('/de/experte/profil/kurs/veranstaltung/'.$event->uuid.'/message', [
		'subject' => 'Anreise',
		'body' => "Erste Zeile.\n\nZweiter Absatz.",
	])->assertRedirect('/de/experte/profil/kurs/veranstaltung/'.$event->uuid);

	$message = Message::query()->where('event_id', $event->id)->firstOrFail();

	expect($message->subject)->toBe('Anreise')
		// Blank lines become paragraphs; there is no editor on the public site
		// ([[ExpertPortalController::paragraphs]]).
		->and($message->body)->toBe('<p>Erste Zeile.</p><p>Zweiter Absatz.</p>')
		->and($message->recipients()->pluck('users.id')->sort()->values()->all())
		->toBe(collect([$first->user_id, $second->user_id])->sort()->values()->all());
});

it('escapes what was typed rather than letting it through as markup', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	expertSeatOn($event);

	$this->actingAs($expert)->post('/de/experte/profil/kurs/veranstaltung/'.$event->uuid.'/message', [
		'subject' => 'Test',
		'body' => '<script>alert(1)</script>',
	])->assertRedirect();

	expect(Message::query()->firstOrFail()->body)->not->toContain('<script>');
});

it('copies the author in only when asked', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	expertSeatOn($event);

	$this->actingAs($expert)->post('/de/experte/profil/kurs/veranstaltung/'.$event->uuid.'/message', [
		'subject' => 'Test', 'body' => 'Text', 'copy_to_me' => '1',
	])->assertRedirect();

	expect(Message::query()->firstOrFail()->recipients()->pluck('users.id'))
		->toContain($expert->id);
});

it('takes an attachment with the form and moves it out of temp', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	expertSeatOn($event);

	$this->actingAs($expert)->post('/de/experte/profil/kurs/veranstaltung/'.$event->uuid.'/message', [
		'subject' => 'Unterlagen',
		'body' => 'Im Anhang.',
		'attachments' => [UploadedFile::fake()->create('handout.pdf', 40)],
	])->assertRedirect();

	$media = Message::query()->firstOrFail()->media()->firstOrFail();

	expect($media->original_name)->toBe('handout.pdf');
	// `UploadMedia` leaves a file in `temp/` and `AttachMedia` is what moves it
	// across — handing the row to `PostMessage` instead would leave it there and
	// `MediaController` would answer 404 for it.
	Storage::disk('public')->assertExists('uploads/'.$media->file);
	Storage::disk('public')->assertMissing('temp/'.$media->file);
});

it('refuses a note from an expert who does not teach the course', function () {
	$expert = expertUser();
	$event = taughtEvent(expertUser(['email' => 'other@viak.test']));
	expertSeatOn($event);

	$this->actingAs($expert)
		->post('/de/experte/profil/kurs/veranstaltung/'.$event->uuid.'/message', [
			'subject' => 'Test', 'body' => 'Text',
		])
		->assertForbidden();

	expect(Message::query()->count())->toBe(0);
});

it('refuses a note from a student, which legacy allowed — finding 4', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	$booking = expertSeatOn($event);

	$this->actingAs($booking->user)
		->post('/de/experte/profil/kurs/veranstaltung/'.$event->uuid.'/message', [
			'subject' => 'Test', 'body' => 'Text',
		])
		->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Course documents
|--------------------------------------------------------------------------
*/

it('uploads course materials and attaches them to the event', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);

	$this->actingAs($expert)->post('/de/experte/profil/kurs/veranstaltung/'.$event->uuid.'/file-upload', [
		'files' => [
			UploadedFile::fake()->create('modelle.zip', 2048),
			UploadedFile::fake()->create('workshop.pdf', 500),
		],
	])->assertRedirect('/de/experte/profil/kurs/veranstaltung/'.$event->uuid);

	expect($event->media()->count())->toBe(2)
		->and($event->media()->pluck('original_name')->all())
		->toBe(['modelle.zip', 'workshop.pdf']);

	Storage::disk('public')->assertExists('uploads/'.$event->media()->first()->file);
});

it('refuses an upload to a course somebody else teaches', function () {
	$expert = expertUser();
	$event = taughtEvent(expertUser(['email' => 'other@viak.test']));

	$this->actingAs($expert)
		->post('/de/experte/profil/kurs/veranstaltung/'.$event->uuid.'/file-upload', [
			'files' => [UploadedFile::fake()->create('modelle.zip', 10)],
		])
		->assertForbidden();

	expect($event->media()->count())->toBe(0);
});

it('deletes a course document, and its file with it', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	$media = Media::factory()->create([
		'mediable_type' => Event::class,
		'mediable_id' => $event->id,
		'original_name' => 'modelle.zip',
	]);
	Storage::disk('public')->put('uploads/'.$media->file, 'x');

	$this->actingAs($expert)->delete(
		'/de/experte/profil/kurs/veranstaltung/'.$event->uuid.'/dokumente/'.$media->uuid
	)->assertRedirect('/de/experte/profil/kurs/veranstaltung/'.$event->uuid);

	expect(Media::query()->whereKey($media->id)->exists())->toBeFalse();
	Storage::disk('public')->assertMissing('uploads/'.$media->file);
});

it('will not delete a message s attachment through the document route', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	$message = Message::factory()->create(['event_id' => $event->id, 'user_id' => $expert->id]);
	$media = Media::factory()->create([
		'mediable_type' => Message::class,
		'mediable_id' => $message->id,
	]);

	// [[MediaPolicy::delete]] admits only a file whose owner is an Event, which
	// is the guarantee legacy's `belongs_to_message` flag tried to give from the
	// template — and never did, because it is always false in that list.
	$this->actingAs($expert)->delete(
		'/de/experte/profil/kurs/veranstaltung/'.$event->uuid.'/dokumente/'.$media->uuid
	)->assertForbidden();

	expect(Media::query()->whereKey($media->id)->exists())->toBeTrue();
});

it('will not delete a document belonging to another course', function () {
	$expert = expertUser();
	$mine = taughtEvent($expert);
	$theirs = taughtEvent($expert, 21);
	$media = Media::factory()->create([
		'mediable_type' => Event::class,
		'mediable_id' => $theirs->id,
	]);

	$this->actingAs($expert)->delete(
		'/de/experte/profil/kurs/veranstaltung/'.$mine->uuid.'/dokumente/'.$media->uuid
	)->assertNotFound();

	expect(Media::query()->whereKey($media->id)->exists())->toBeTrue();
});

it('lists an event s materials with the download link the policy gates', function () {
	$expert = expertUser();
	$event = taughtEvent($expert);
	$media = Media::factory()->create([
		'mediable_type' => Event::class,
		'mediable_id' => $event->id,
		'original_name' => 'modelle.zip',
		'size' => 1_536_000,
	]);

	$this->actingAs($expert)->get('/de/experte/profil/kurs/veranstaltung/'.$event->uuid)
		->assertOk()
		->assertSee('modelle.zip')
		// `Vue.filter('fileSize')` is base 1000 with two decimals and the
		// trailing zeros trimmed ([[x-site.file-row]]).
		->assertSee('1.54 MB')
		->assertSee(route('media.download', $media->uuid), escape: false);
});
