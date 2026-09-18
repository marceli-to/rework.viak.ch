<?php

declare(strict_types=1);

use App\Models\Course;

/**
 * Head tags legacy has none of, checked against production 2026-09-18
 * ([[00-foundation]]).
 */
it('emits a canonical tag on the host the site is indexed under', function () {
	$this->get('/de')
		->assertOk()
		->assertSee('<link rel="canonical" href="https://visualisierungs-akademie.ch/de">', false);
});

it('emits hreflang for every locale that has URLs', function () {
	$this->get('/de')->assertSee('hreflang="de"', false);
});

/** A dead render-blocking request on every page of the live site. */
it('does not load the dead neuzeit-grotesk Typekit kit', function () {
	$this->get('/de')->assertDontSee('kcs4ept', false);
});

it('ships Alpine on the public site and no Vue', function () {
	// Vite hashes the filenames, so match the entry rather than the asset:
	// `assets/site-*.js` is the Alpine bundle, `assets/app-*.js` is the Vue SPA.
	$html = $this->get('/de')->getContent();

	expect($html)->toMatch('#assets/site-[^"]+\.js#')
		->not->toMatch('#assets/app-[^"]+\.js#');
});

it('shows the basket count from the shared store', function () {
	$this->get('/de')->assertSee('$store.basket.count', false);
});

it('renders a course page through the layout', function () {
	Course::factory()->create([
		'title' => ['de' => 'Rhino Einstiegskurs'],
		'slug' => ['de' => 'rhino-einstiegskurs'],
		'publish' => true,
	]);

	$this->get('/de/kurs/rhino-einstiegskurs')
		->assertOk()
		->assertSee('Rhino Einstiegskurs')
		->assertSee('rel="canonical"', false);
});
