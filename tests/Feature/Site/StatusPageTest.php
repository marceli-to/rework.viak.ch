<?php

declare(strict_types=1);

/**
 * *Projektstand* (2026-10-20): the open points for the client's review, on
 * an unlisted page that search engines are told to leave alone.
 */
it('lists what is open, and keeps itself out of search engines', function () {
	$this->get('/de/projektstand')
		->assertOk()
		->assertSee('Projektstand')
		->assertSee('Fragen an VIAK')
		->assertSee('Noch nicht gebaut')
		->assertSee('Vor dem Go-live')
		->assertSee('<meta name="robots" content="noindex, nofollow">', false)
		->assertDontSee('—');
});

it('is in no sitemap', function () {
	$this->get('/sitemap.xml')->assertOk()->assertDontSee('projektstand');
});

it('leaves every other page to the search engines', function () {
	$this->get('/de/kontakt')->assertOk()->assertDontSee('noindex', false);
});
