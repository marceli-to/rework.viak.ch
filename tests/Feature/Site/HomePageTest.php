<?php

declare(strict_types=1);

use App\Enums\EventState;
use App\Models\Course;
use App\Models\Event;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Page;
use App\Models\Software;
use App\Models\Testimonial;

/**
 * The homepage, as far as it is built ([[HomeController]]): the call band
 * (the review's marker 2), *Nächste Kurstermine* (3), *Beliebte Angebote*
 * (5, 6), the About teaser (9) and the testimonials (10). The Vorhaben tiles
 * are `ProjectPageTest`'s.
 */
it('carries the call band with the number and the way to a call back', function () {
	$this->get('/de')
		->assertOk()
		->assertSee('Nicht sicher, was du brauchst?')
		->assertSee('href="tel:+41435014040"', false)
		->assertSee('href="/de/kontakt#nachricht"', false);
});

it('lists the next six bookable dates in order, each to its course', function () {
	foreach (range(1, 7) as $days) {
		$course = Course::factory()->create(['title' => ['de' => "Kurs {$days}"], 'slug' => ['de' => "kurs-{$days}"]]);
		Event::factory()->for($course)->create(['date' => now()->addDays($days * 2)->toDateString()]);
	}

	$html = $this->get('/de')
		->assertSee('Nächste Kurstermine')
		->assertSeeInOrder(['Nächste Kurstermine', 'Alle Kurse und Termine', 'Kurs 1', 'Kurs 2', 'Kurs 3', 'Kurs 4', 'Kurs 5', 'Kurs 6'])
		->assertSee('href="/de/kurs/kurs-1"', false)
		->getContent();

	expect($html)->not->toContain('Kurs 7');
});

it('leaves out what cannot be booked: past, cancelled, unpublished, or of an unpublished course', function () {
	Event::factory()->for(Course::factory()->create(['title' => ['de' => 'Offen']]))->create();
	Event::factory()->past()->for(Course::factory()->create(['title' => ['de' => 'Vorbei']]))->create();
	Event::factory()->for(Course::factory()->create(['title' => ['de' => 'Abgesagt']]))->create(['state' => EventState::Cancelled]);
	Event::factory()->for(Course::factory()->create(['title' => ['de' => 'Versteckt']]))->create(['publish' => false]);
	Event::factory()->for(Course::factory()->unpublished()->create(['title' => ['de' => 'Entwurf']]))->create();

	$this->get('/de')
		->assertSee('Offen')
		->assertDontSee('Vorbei')
		->assertDontSee('Abgesagt')
		->assertDontSee('Versteckt')
		->assertDontSee('Entwurf');
});

it('shows no number and no state badge on a row, which are the dashboard’s', function () {
	$course = Course::factory()->create(['number' => 42, 'title' => ['de' => 'Rhino Einstiegskurs']]);
	Event::factory()->for($course)->create();

	$this->get('/de')
		->assertSee('>Rhino Einstiegskurs</a>', false)
		->assertDontSee('42 Rhino')
		->assertDontSee('Kurs offen, wird bestätigt');
});

it('leaves the dates out when there are none', function () {
	$this->get('/de')->assertOk()->assertDontSee('Nächste Kurstermine');
});

it('lists the courses flagged Beliebt, published only, in the catalogue’s order', function () {
	Course::factory()->create(['title' => ['de' => 'Zweiter'], 'featured' => true, 'order' => 2]);
	Course::factory()->create(['title' => ['de' => 'Erster'], 'featured' => true, 'order' => 1]);
	Course::factory()->create(['title' => ['de' => 'Nicht beliebt']]);
	Course::factory()->unpublished()->create(['title' => ['de' => 'Entwurf'], 'featured' => true]);

	$this->get('/de')
		->assertSeeInOrder(['Beliebte Angebote', 'Erster', 'Zweiter'])
		->assertDontSee('Nicht beliebt')
		->assertDontSee('Entwurf');
});

it('lists the software flagged Beliebt after the courses, only what the shop sells', function () {
	$sold = function (string $title, bool $featured = true, bool $listed = true): Software {
		$software = Software::create(['title' => ['de' => $title], 'publish' => true, 'featured' => $featured]);
		$product = LicenceProduct::factory()->create(['software_id' => $software->id]);
		LicenceVariant::factory()->create(['licence_product_id' => $product->id, 'listed' => $listed]);

		return $software;
	};

	Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs'], 'featured' => true]);
	$sold('Twinmotion');
	$sold('Lumion', featured: false);
	$sold('Veras', listed: false);

	$this->get('/de')
		->assertSeeInOrder(['Beliebte Angebote', 'Kurs', 'Rhino Einstiegskurs', 'Software', 'Twinmotion'])
		->assertDontSee('Lumion')
		->assertDontSee('Veras');
});

it('shows Beliebte Angebote for flagged software alone', function () {
	$software = Software::create(['title' => ['de' => 'Twinmotion'], 'publish' => true, 'featured' => true]);
	$product = LicenceProduct::factory()->create(['software_id' => $software->id]);
	LicenceVariant::factory()->create(['licence_product_id' => $product->id]);

	$this->get('/de')->assertSeeInOrder(['Beliebte Angebote', 'Twinmotion']);
});

it('leaves Beliebte Angebote out when no course is flagged', function () {
	Course::factory()->create();

	$this->get('/de')->assertDontSee('Beliebte Angebote');
});

it('carries the About teaser with the way to Über uns', function () {
	$this->get('/de')
		->assertSee('Warum bei der VIAK')
		->assertSee('href="/de/ueber-uns"', false);
});

it('shows the About teaser’s copy as edited in the dashboard', function () {
	Page::for('home-about')->update(['content' => ['title' => 'Wer wir sind', 'text' => '<p>Ein Studio in Zürich.</p>']]);

	$this->get('/de')
		->assertSeeInOrder(['Wer wir sind', 'Ein Studio in Zürich.', 'Mehr über uns'])
		->assertDontSee('Warum bei der VIAK');
});

it('shows the testimonials picked for the homepage, published only, in their order', function () {
	$a = Testimonial::factory()->create(['name' => 'Anna Muster']);
	$b = Testimonial::factory()->create(['name' => 'Beat Beispiel']);
	$hidden = Testimonial::factory()->create(['name' => 'Verborgen', 'publish' => false]);
	Page::for('home')->testimonials()->attach([$b->id => ['order' => 1], $a->id => ['order' => 2], $hidden->id => ['order' => 3]]);

	$this->get('/de')
		->assertSeeInOrder(['Kundenmeinungen', 'Beat Beispiel', 'Anna Muster'])
		->assertDontSee('Verborgen');
});

it('leaves Kundenmeinungen out when none is picked', function () {
	Testimonial::factory()->create();

	$this->get('/de')->assertDontSee('Kundenmeinungen');
});
