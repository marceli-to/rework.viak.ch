<?php

declare(strict_types=1);

/**
 * The Kontakt page ([[09-public-site]]).
 *
 * Static copy and a map, measured against production on 2026-09-24. What is
 * asserted is what the measurement cannot see: that the legal copy came across
 * whole, that its one download resolves, that the shut blocks start shut, and
 * that the map only reaches for Google when there is a key to reach with.
 */
it('renders on legacy’s url, and the nav lights Kontakt', function () {
	$response = $this->get('/de/kontakt')
		->assertOk()
		->assertSee('<title>Kontakt • Visualisierungs-Akademie</title>', false)
		->assertSee('>Kontakt</h1>', false)
		->assertSee('Get in touch');

	expect($response->getContent())->toMatch('/<a[^>]*href="\/de\/kontakt"[^>]*text-teal/s');
});

it('carries the address, and the phone and mail as links', function () {
	$this->get('/de/kontakt')
		->assertSee('Limmatstrasse 291<br>CH-8005 Zürich', false)
		->assertSee('href="tel:+41435014040"', false)
		->assertSee('href="mailto:hallo@visualisierungs-akademie.ch"', false);
});

it('opens Anreise and leaves Über uns and Impressum shut, without a flash', function () {
	$html = $this->get('/de/kontakt')->getContent();

	expect($html)
		->toContain('x-data="{ open: true }"')
		->and(substr_count($html, 'x-data="{ open: false }"'))->toBe(2)
		// A shut block is cloaked, or it paints open for a frame first.
		->and(preg_match_all('/<div x-show="open"\s+x-cloak\s*>/', $html))->toBe(2);
});

/**
 * Converted from legacy's `__()`-wrapped partials by script. A dropped or
 * doubled quote there would lose a paragraph silently, so the checks sit at
 * the start, the middle and the very end of the longest one.
 */
it('carries the Impressum and the whole Datenschutzerklärung', function () {
	$this->get('/de/kontakt')
		->assertSee('MWST-Nr. CHE-110.279.761 MWST')
		->assertSee('Seit 20 Jahren dabei und doch brandneu!')
		->assertSee("Seminare unter dem Titel '2sek Manager'", false)
		->assertSee('1. Kontaktadressen')
		->assertSee('8.3 Zählpixel')
		->assertSee('Wir können diese Datenschutzerklärung jederzeit anpassen und ergänzen.');
});

it('links the AGB to a file that exists, at legacy’s path', function () {
	$path = '/media/downloads/Visualisierungs-Akademie_AGB_Jul24.pdf';

	$this->get('/de/kontakt')->assertSee('href="'.$path.'"', false);

	expect(public_path($path))->toBeFile();
});

it('draws the map’s box without a key, and loads Google only with one', function () {
	config(['services.google_maps.key' => null]);

	$this->get('/de/kontakt')
		->assertSee('id="js-map"', false)
		->assertDontSee('maps.googleapis.com');

	config(['services.google_maps.key' => 'test-key']);

	$this->get('/de/kontakt')->assertSee('maps.googleapis.com/maps/api/js?key=test-key', false);
});
