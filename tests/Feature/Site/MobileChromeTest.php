<?php

declare(strict_types=1);

use App\Models\Course;

/**
 * The phone layout ([[00-foundation]]).
 *
 * Asserted against the markup rather than a viewport, because the rules are
 * media queries and the classes are what carries them. The behaviour they drive
 * — the panel opening, the class binding flipping — was checked in the browser.
 */
it('puts the burger at the bottom right, out of the header bar', function () {
	$html = $this->get('/de')->getContent();

	// `icons/_menu.scss`: fixed, 32×24, bottom 20 right 20, above the page.
	expect($html)->toContain('fixed right-20 bottom-20 z-[99] h-24 w-32 sm:hidden');
});

it('gives the mobile menu legacy’s teal panel and white frame', function () {
	$html = $this->get('/de')->getContent();

	expect($html)
		->toContain('border-8 border-b-0 border-white bg-teal')
		->toContain('mt-72');
});

/** On a phone legacy lists Profil as a word; on desktop it is the icon. */
it('adds Profil to the mobile menu', function () {
	$html = $this->get('/de')->getContent();

	$panel = mb_substr($html, mb_strpos($html, 'border-8 border-b-0'));

	expect($panel)->toContain('Profil')
		->toContain('mailto:hallo@visualisierungs-akademie.ch')
		->toContain('instagram.com/viak.ch')
		->toContain('facebook.com/ViAkSchweiz');
});

it('shows a filter trigger beside the page title on a phone', function () {
	Course::factory()->create(['slug' => ['de' => 'x'], 'publish' => true]);

	$this->get('/de/kurse')
		->assertSee('aria-label="Filter anzeigen"', false)
		->assertSee("\$dispatch('open-filter')", false);
});

/**
 * `components/_filter.scss` under `bp-xs`: a fixed, full-height white panel.
 * `pt-88` is legacy's own arithmetic — 48 of header, 24 of header margin, 16 of
 * body padding — so its first line lands where the page's would.
 */
it('makes the filter a full-screen panel on a phone and a column above it', function () {
	Course::factory()->create(['slug' => ['de' => 'y'], 'publish' => true]);

	$this->get('/de/kurse')
		->assertSee('fixed inset-0 z-[100] h-full w-full overflow-y-auto bg-white px-16 pt-88 pb-48 sm:static', false);
});

/**
 * The breakpoint is CSS, not JavaScript. Binding `x-show` to a media query
 * would not survive a resize, and would hide the desktop column until Alpine
 * had started.
 */
it('hides the filter panel with a class rather than a media query in script', function () {
	Course::factory()->create(['slug' => ['de' => 'z'], 'publish' => true]);

	$this->get('/de/kurse')
		->assertSee("open ? '' : 'max-sm:hidden'", false)
		->assertDontSee('matchMedia', false);
});
