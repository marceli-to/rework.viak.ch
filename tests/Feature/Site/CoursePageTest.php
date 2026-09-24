<?php

declare(strict_types=1);

use App\Enums\EventState;
use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseVideo;
use App\Models\Event;
use App\Models\Location;
use App\Models\User;
use App\Support\SiteUrl;
use Illuminate\Testing\TestResponse;

/**
 * The course detail page ([[09-public-site]]).
 *
 * Rebuilt against production on 2026-09-21; the pixel measurements live in the
 * chunk doc and in the component comments. What is asserted here is what a
 * measurement cannot catch: that each block appears when it has content and
 * stays away when it does not, that the states of the event row are the ones
 * legacy shows, and that the two things the rebuild uncovered — the videos
 * table and the Open Graph image — actually reach the page.
 */
function detailCourse(array $attributes = []): Course
{
	return Course::factory()->create([
		'title' => ['de' => 'Blender Einführungskurs'],
		'slug' => ['de' => 'blender-einfuehrungskurs'],
		'publish' => true,
		...$attributes,
	]);
}

function coursePage(?Course $course = null): TestResponse
{
	$course ??= detailCourse();

	return test()->get('/de/kurs/'.$course->getTranslation('slug', 'de'));
}

it('titles the tab with the course and the header row with Kurse', function () {
	$response = coursePage()->assertOk();

	// Legacy keeps `seo_title` and `page_title` apart, and this is the one page
	// where they differ. The `h1` in the header is the phone's title row.
	$response->assertSee('<title>Blender Einführungskurs • Visualisierungs-Akademie</title>', false);
	$response->assertSee('>Kurse</h1>', false);
});

it('shows a collapsible only when it has something in it', function () {
	coursePage(detailCourse())
		->assertSee('Aktuelle Kurse')
		->assertDontSee('Videos')
		->assertDontSee('Facts')
		->assertDontSee('Detailbeschrieb')
		->assertDontSee('Weitere Informationen');
});

it('renders the facts as three columns of editor HTML', function () {
	$course = detailCourse(['facts' => [
		['de' => '<p><strong>Zielpublikum:</strong></p>'],
		['de' => '<p><strong>Kursinhalt:</strong></p>'],
		['de' => '<p><strong>Voraussetzungen:</strong></p>'],
	]]);

	// Escaped with `{{ }}` these render as literal <strong> tags, which is what
	// the ported rows contain — the same trap the facts block had on the list.
	coursePage($course)
		->assertSee('Facts')
		->assertSee('<strong>Zielpublikum:</strong>', false)
		->assertSee('<strong>Voraussetzungen:</strong>', false);
});

/**
 * `course_videos` is the table chunk 01 never carried across — 19 of 32 courses
 * have a row and were quietly losing a section of their page.
 */
it('renders the videos legacy has and the rework had lost', function () {
	$course = detailCourse();
	CourseVideo::factory()->for($course)->create([
		'title' => ['de' => 'Ein mit Blender erstellter Kurzfilm:'],
		'code' => '<iframe src="https://www.youtube.com/embed/abc"></iframe>',
	]);

	coursePage($course)
		->assertSee('Videos')
		->assertSee('Ein mit Blender erstellter Kurzfilm:')
		// `code` is editor-pasted markup and prints unescaped, as legacy does.
		->assertSee('<iframe src="https://www.youtube.com/embed/abc"></iframe>', false);
});

it('leaves an unpublished video off the page', function () {
	$course = detailCourse();
	CourseVideo::factory()->for($course)->unpublished()->create([
		'title' => ['de' => 'Noch nicht freigegeben'],
	]);

	coursePage($course)->assertDontSee('Noch nicht freigegeben');
});

it('shows a dated row with its times, place and expert', function () {
	$course = detailCourse();
	$location = Location::factory()->create(['description' => ['de' => 'Zürich, Hardturmstrasse']]);
	$event = Event::factory()->for($course)->create([
		'location_id' => $location->id,
		'date' => '2026-10-15',
		'registration_until' => '2026-10-01',
		'state' => EventState::Planned,
		'fee' => 899,
	]);
	$event->dates()->create(['date' => '2026-10-15', 'time_start' => '08:30:00', 'time_end' => '17:00:00']);
	$expert = User::factory()->create(['first_name' => 'Helge', 'last_name' => 'Maus']);
	$event->experts()->attach($expert);

	coursePage($course)
		->assertSee('15. Oktober 2026')
		// `08:30:00` in the column, `08.30` on the page — legacy's separator.
		->assertSee('08.30 – 17.00 Uhr')
		->assertSee('Zürich, Hardturmstrasse')
		->assertSee('mit Helge Maus')
		->assertSee('Anmeldung möglich bis 01.10.2026')
		->assertSee('899.00')
		->assertSee('Buchen');
});

it('says Onlinekurs instead of a place when the event is online', function () {
	$course = detailCourse();
	$location = Location::factory()->create(['description' => ['de' => 'Zürich, Hardturmstrasse']]);
	Event::factory()->for($course)->create(['online' => true, 'location_id' => $location->id]);

	coursePage($course)
		->assertSee('Onlinekurs')
		->assertDontSee('Zürich, Hardturmstrasse');
});

it('distinguishes a confirmed course from one still gathering people', function () {
	$planned = detailCourse();
	Event::factory()->for($planned)->create(['state' => EventState::Planned]);
	coursePage($planned)->assertSee('Kurs offen, wird bestätigt');

	$confirmed = detailCourse(['slug' => ['de' => 'bestaetigt'], 'title' => ['de' => 'Bestätigt']]);
	Event::factory()->for($confirmed)->in(EventState::Confirmed)->create();
	coursePage($confirmed)->assertSee('Kurs findet statt');
});

it('offers the booking to browse rather than to buy when a course has no dates', function () {
	coursePage(detailCourse())
		->assertSee('Neues Kursdatum folgt in Kürze')
		->assertDontSee('Buchen');
});

it('offers a guest the login rather than the bookmark endpoint', function () {
	$course = detailCourse();
	Event::factory()->for($course)->create();

	// The endpoints are behind `auth:sanctum`; legacy answers a guest with a
	// dialog saying the Merkliste needs an account, and this says it in a link.
	coursePage($course)
		->assertSee('href="'.route('login').'" title="Zur Merkliste hinzufügen"', false)
		->assertDontSee('x-data="bookmark(', false);
});

it('gives a signed-in visitor the bookmark toggle, with its current state', function () {
	$course = detailCourse();
	$event = Event::factory()->for($course)->create();
	$user = User::factory()->create();
	$user->bookmarks()->attach($event);

	$this->actingAs($user)
		->get('/de/kurs/'.$course->getTranslation('slug', 'de'))
		->assertOk()
		->assertSee('saved: true })', false);
});

/**
 * Legacy's `getBrowse()` sends the first course's *previous* to the last one and
 * the last course's *next* to the first, so the pair is always two links.
 */
it('wraps the previous and next links around the ends of the catalogue', function () {
	$first = detailCourse(['title' => ['de' => 'Erster Kurs'], 'slug' => ['de' => 'erster'], 'order' => 1]);
	$middle = detailCourse(['title' => ['de' => 'Zweiter Kurs'], 'slug' => ['de' => 'zweiter'], 'order' => 2]);
	$last = detailCourse(['title' => ['de' => 'Dritter Kurs'], 'slug' => ['de' => 'dritter'], 'order' => 3]);

	coursePage($first)
		->assertSee('Weitere Kurse')
		->assertSee('Dritter Kurs')
		->assertSee('Zweiter Kurs');

	coursePage($last)
		->assertSee('Zweiter Kurs')
		->assertSee('Erster Kurs');

	coursePage($middle)
		->assertSee('Erster Kurs')
		->assertSee('Dritter Kurs');
});

it('has no browse pair when the catalogue holds one course', function () {
	coursePage(detailCourse())->assertDontSee('Weitere Kurse');
});

/**
 * The rental question, which is the reason `Buchen` is not always an add
 * ([[09-public-site]]).
 *
 * `Basket.vue` renders two different buttons off `hasRentals`, and the one that
 * asks has to ask *before* the add: the rental and its price are frozen onto
 * the booking, and an event whose room has no machines cannot sell one at all
 * ([[PriceBasket]]). The button therefore carries the flag and the store
 * decides, so this asserts the flag reaches the page.
 */
it('tells the Buchen button whether the event can sell a laptop', function () {
	$course = detailCourse();
	Event::factory()->for($course)->create(['rentals_available' => 2]);

	coursePage($course)->assertSee('rentals: true }', false);

	$without = detailCourse(['slug' => ['de' => 'ohne-miete'], 'title' => ['de' => 'Ohne Miete']]);
	Event::factory()->for($without)->create(['rentals_available' => 0]);

	coursePage($without)->assertSee('rentals: false }', false);
});

it('stops offering a laptop once every one is rented', function () {
	$course = detailCourse();
	$event = Event::factory()->for($course)->create(['rentals_available' => 2]);
	Booking::factory()->for($event)->count(2)->create(['has_rental' => true]);

	// Legacy's `has_rentals_available`: the room's count less those rented.
	coursePage($course)->assertSee('rentals: false }', false);
});

it('carries the rental dialog and the confirmation once, however many events it lists', function () {
	$course = detailCourse();
	Event::factory()->for($course)->count(3)->create(['rentals_available' => 2]);

	$page = coursePage($course);

	// Legacy renders both `<notification>` tags inside every `basket-button`,
	// which on this page would be six modals and six copies of the text. Counted
	// on the `x-show` rather than on the message: *Computer mieten* is also a
	// phrase inside the dialog's own body text.
	expect(substr_count($page->getContent(), 'x-show="$store.basket.rentalFor"'))->toBe(1)
		->and(substr_count($page->getContent(), 'x-show="$store.basket.confirmed"'))->toBe(1);

	$page->assertSee('Nein, ich bringe meinen eigenen Laptop')
		->assertSee(SiteUrl::checkout('basket'));
});
