<?php

declare(strict_types=1);

use App\Enums\EventState;
use App\Models\Course;
use App\Models\Event;

/**
 * The homepage, as far as it is built ([[HomeController]]): the call band
 * (the review's marker 2) and *Nächste Kurstermine* (3). The Vorhaben tiles
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
		->assertSeeInOrder(['Kurs 1', 'Kurs 2', 'Kurs 3', 'Kurs 4', 'Kurs 5', 'Kurs 6', 'Alle Kurse und Termine'])
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
