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

/**
 * `.icon-filter` — **one** control, fixed at 26/16 over z-index 101, drawing the
 * funnel when the panel is shut and the cross when it is open. It only *looks*
 * like part of the title row, which is how an earlier pass came to put a trigger
 * in the header and a second, larger cross inside the panel.
 */
it('gives the filter a single fixed control that swaps funnel for cross', function () {
	Course::factory()->create(['slug' => ['de' => 'x'], 'publish' => true]);

	$html = $this->get('/de/kurse')->getContent();

	preg_match('#<button[^>]*aria-label="Filter"[^>]*>[\s\S]*?</button>#', $html, $control);

	expect($html)->toContain('class="fixed top-26 right-16 z-[101] block h-22 w-22 sm:hidden"');

	expect($control[0] ?? 'no control')
		->toContain('<span :class="{ hidden: open }">')
		->toContain('<span class="hidden" :class="{ hidden: ! open }">')
		// Both glyphs are the 22×22 pair. The 31×30 `large` cross belongs to the
		// menu (`.icon-menu__cross`), and the panel no longer has one of its own.
		->toContain('viewBox="0 0 22 22"')
		->not->toContain('viewBox="0 0 31 30"');

	expect(substr_count($control[0] ?? '', 'viewBox="0 0 22 22"'))->toBe(2)
		->and($html)->not->toContain('aria-label="Filter schliessen"');

	// And the header title row is back to holding only its heading.
	expect($html)
		->toContain('flex min-h-48 w-full items-end border-b border-black pb-12 sm:hidden')
		->not->toContain("\$dispatch('open-filter')");
});

/** `.site-menu__footer` has no padding: 8px left is the icons' own, 12px right is the cross's. */
it('sets the menu footer offsets from the icons rather than from the footer', function () {
	$html = $this->get('/de')->getContent();

	expect($html)
		->toContain('<footer class="flex h-64 items-center justify-between bg-white">')
		->toContain('<div class="ml-8 flex items-center gap-16">')
		->toContain('aria-label="Menü schliessen" class="mr-12"');
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
