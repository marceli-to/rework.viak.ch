<?php

declare(strict_types=1);

use App\Models\Course;

/**
 * The public URL structure ([[00-foundation]]).
 *
 * These are indexed URLs on a live site, so each assertion here is a piece of
 * ranking rather than a preference. Measured against production 2026-09-18.
 */
it('redirects the root to the language root', function () {
	// Legacy serves both with 200 and no canonical — duplicate content, live.
	$this->get('/')->assertRedirect('/de')->assertStatus(301);
});

it('serves the German home page under its prefix', function () {
	$this->get('/de')->assertOk();
});

it('keeps the course list where it has always been', function () {
	$this->get('/de/kurse')->assertOk();
});

/** Unprefixed URLs never existed: production returns 404 for `/kurse`. */
it('does not answer without the locale prefix', function () {
	$this->get('/kurse')->assertNotFound();
});

it('serves a course by its slug, at the singular segment', function () {
	$course = Course::factory()->create([
		'title' => ['de' => 'Rhino Einstiegskurs'],
		'slug' => ['de' => 'rhino-einstiegskurs'],
		'publish' => true,
	]);

	$this->get('/de/kurs/rhino-einstiegskurs')->assertOk();
});

/**
 * The legacy detail URL carried a slug *and* a uuid, and the uuid is what
 * resolved. A 301 keeps three years of ranking.
 */
it('redirects the legacy uuid URL to the slug form', function () {
	$course = Course::factory()->create([
		'slug' => ['de' => 'rhino-einstiegskurs'],
		'publish' => true,
	]);

	$this->get("/de/kurs/rhino-einstiegskurs/{$course->uuid}")
		->assertRedirect('/de/kurs/rhino-einstiegskurs')
		->assertStatus(301);
});

/** The slug was decorative — `/de/kurs/anything/{uuid}` renders the course. */
it('redirects even when the legacy slug was wrong', function () {
	$course = Course::factory()->create([
		'slug' => ['de' => 'rhino-einstiegskurs'],
		'publish' => true,
	]);

	$this->get("/de/kurs/irgendwas/{$course->uuid}")
		->assertRedirect('/de/kurs/rhino-einstiegskurs')
		->assertStatus(301);
});

it('404s a legacy URL whose course is gone', function () {
	$this->get('/de/kurs/weg/'.fake()->uuid())->assertNotFound();
});
