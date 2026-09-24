<?php

declare(strict_types=1);

use App\Enums\EventState;
use App\Models\Course;
use App\Models\Event;
use App\Models\ExpertProfile;
use App\Models\Media;
use App\Models\User;
use App\Support\SiteUrl;

/**
 * The Experten page and one expert's page ([[09-public-site]]).
 *
 * Both were measured against production on 2026-09-24, and the numbers are in
 * the views. What is asserted here is what a measurement cannot catch: who is
 * listed, which courses appear against them and in what order, and that
 * legacy's URLs — indexed, and with a uuid in them — keep resolving.
 */
function listedExpert(string $first = 'Remo', string $last = 'Kast', array $profile = []): User
{
	$expert = User::factory()->expert()->create(['first_name' => $first, 'last_name' => $last]);
	ExpertProfile::factory()->for($expert)->create($profile);

	return $expert;
}

function teaches(User $expert, string $course, array $event = [], array $courseAttributes = []): Event
{
	$event = Event::factory()
		->for(Course::factory()->create(['title' => ['de' => $course], ...$courseAttributes]))
		->create(['date' => today()->addWeek(), ...$event]);

	$event->experts()->attach($expert);

	return $event;
}

it('lists the experts with both flags set, in their order', function () {
	listedExpert('Helge', 'Maus', ['order' => 2]);
	listedExpert('Remo', 'Kast', ['order' => 1]);
	listedExpert('Nicht', 'Sichtbar', ['visible' => false]);
	listedExpert('Nicht', 'Publiziert', ['publish' => false]);

	$this->get('/de/experten')
		->assertOk()
		->assertSee('<title>Experten • Visualisierungs-Akademie</title>', false)
		->assertSeeInOrder(['Remo Kast', 'Helge Maus'])
		->assertDontSee('Sichtbar')
		->assertDontSee('Publiziert');
});

it('links each card to legacy’s url, slug spelled the way legacy spells it', function () {
	$expert = listedExpert('Daniel', 'Nähring');

	expect(SiteUrl::expert($expert))->toBe("/de/experte/daniel-naehring/{$expert->uuid}")
		->and(SiteUrl::expertSlug(listedExpert('Güneş', 'Direk')))->toBe('guenes-direk');

	$this->get('/de/experten')->assertSee("/de/experte/daniel-naehring/{$expert->uuid}", false);
});

/**
 * Legacy's order is the order the dates were entered, which is id order — and
 * on seven of the ten live experts that differs from date order.
 */
it('names the courses an expert teaches, once each, in the order legacy does', function () {
	$expert = listedExpert();
	teaches($expert, 'Interior Design mit SketchUp', ['date' => today()->addMonths(2)]);
	$later = teaches($expert, 'Visualisieren mit SketchUp', ['date' => today()->addMonth()]);
	teaches($expert, 'Visualisieren mit SketchUp', ['date' => today()->addMonths(3), 'course_id' => $later->course_id]);

	$this->get('/de/experten')
		->assertSee('Kurse:')
		->assertSeeInOrder(['Interior Design mit SketchUp', 'Visualisieren mit SketchUp']);

	expect(substr_count($this->get('/de/experten')->getContent(), 'Visualisieren mit SketchUp'))->toBe(1);
});

it('leaves out dates that have run, are cancelled, unpublished, or belong to a hidden course', function () {
	$expert = listedExpert();
	teaches($expert, 'Vergangen', ['date' => today()->subDay()]);
	teaches($expert, 'Abgesagt', ['state' => EventState::Cancelled]);
	teaches($expert, 'Entwurf', ['publish' => false]);
	teaches($expert, 'Versteckt', [], ['publish' => false]);

	$this->get('/de/experten')
		->assertDontSee('Kurse:')
		->assertDontSee('Vergangen')
		->assertDontSee('Abgesagt')
		->assertDontSee('Entwurf')
		->assertDontSee('Versteckt');
});

it('shows the expert’s page with the bio, and the courses linked', function () {
	$expert = listedExpert(profile: ['title' => 'Der Macher', 'description' => '<p>Keine Angst vor <strong>staubigen</strong> Händen.</p>']);
	$event = teaches($expert, 'SketchUp Kurs', courseAttributes: ['slug' => ['de' => 'sketchup-kurs']]);

	$this->get(SiteUrl::expert($expert))
		->assertOk()
		->assertSee('<title>Remo Kast • Visualisierungs-Akademie</title>', false)
		// The phone's header row says *Experte*, legacy's `page_title`.
		->assertSee('>Experte</h1>', false)
		->assertSee('Der Macher')
		->assertSee('<strong>staubigen</strong>', false)
		->assertSee('href="/de/kurs/sketchup-kurs"', false);
});

it('leaves the course list off an expert with nothing coming up', function () {
	$this->get(SiteUrl::expert(listedExpert()))
		->assertOk()
		->assertDontSee('>Kurse</h2>', false);
});

it('uses the square teaser on the card and the 16:9 visual on the page', function () {
	$expert = listedExpert();
	Media::factory()->for($expert, 'mediable')->create(['file' => 'kast-visual.jpg']);
	Media::factory()->for($expert, 'mediable')->create(['file' => 'kast-teaser.jpg', 'is_teaser' => true]);
	Media::factory()->for($expert, 'mediable')->create(['file' => 'kast-og.jpg', 'is_og' => true]);

	$this->get('/de/experten')->assertSee('kast-teaser.jpg')->assertDontSee('kast-visual.jpg');

	$this->get(SiteUrl::expert($expert))
		->assertSee('kast-visual.jpg')
		->assertDontSee('kast-teaser.jpg');
});

it('301s a stale slug to the current one, since the uuid is what resolves', function () {
	$expert = listedExpert();

	$this->get("/de/experte/remo-alt/{$expert->uuid}")
		->assertMovedPermanently()
		->assertRedirect(SiteUrl::expert($expert));
});

it('404s an expert who is not listed, and a role-holder with no profile', function () {
	$hidden = listedExpert(profile: ['visible' => false]);
	$bare = User::factory()->expert()->create();

	$this->get(SiteUrl::expert($hidden))->assertNotFound();
	$this->get(SiteUrl::expert($bare))->assertNotFound();
});

it('does not take the expert portal’s paths', function () {
	// `/de/experte/profil/…` shares the segment; the uuid constraint is what
	// keeps a portal URL from being read as a slug and a uuid.
	$this->get('/de/experte/profil/bearbeiten')->assertRedirect(route('login'));
});

it('points the nav at the Experten page and lights it there', function () {
	$this->get('/de/kurse')->assertSee('href="/de/experten"', false);

	expect($this->get('/de/experten')->getContent())
		->toMatch('/<a[^>]*href="\/de\/experten"[^>]*text-teal/s');
});
