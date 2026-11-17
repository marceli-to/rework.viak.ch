<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Event;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\News;
use App\Models\Software;
use App\Models\User;
use App\Support\NewsLink;

/**
 * **News on the homepage** (Oliver in Roxy, 2026-11-12, [[News]]): edited in
 * *Seiteninhalte → News*, the first two published beside *Nächste
 * Kurstermine*, each linking to a page picked from a list.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

it('keeps news to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/news')->assertForbidden();
});

it('writes a news item, the newest on top, and orders them by drag', function () {
	$older = News::factory()->create(['order' => 1]);

	$uuid = $this->actingAs($this->admin)->postJson('/api/admin/news', [
		'title' => ' Rhino 9 jetzt verfügbar ', 'text' => '', 'link' => 'page:training', 'publish' => true,
	])->assertCreated()
		->assertJsonPath('data.title', 'Rhino 9 jetzt verfügbar')
		->assertJsonPath('data.link_label', 'Firmenschulung')
		->assertJsonPath('data.url', '/de/firmenschulung')
		->json('data.uuid');

	expect(News::query()->ordered()->pluck('uuid')->all())->toBe([$uuid, $older->uuid])
		->and(News::where('uuid', $uuid)->first()->getTranslation('text', 'de', false))->toBe('');

	$this->postJson('/api/admin/news/order', ['news' => [$older->uuid, $uuid]])->assertNoContent();
	expect(News::query()->ordered()->pluck('uuid')->all())->toBe([$older->uuid, $uuid]);

	$this->deleteJson("/api/admin/news/{$uuid}")->assertNoContent();
	expect(News::count())->toBe(1);
});

it('takes only a link from the list, or none', function () {
	$this->actingAs($this->admin)->postJson('/api/admin/news', ['title' => 'X', 'link' => 'https://example.com', 'publish' => true])
		->assertJsonValidationErrors('link');

	$this->postJson('/api/admin/news', ['title' => 'X', 'link' => '', 'publish' => true])
		->assertCreated()->assertJsonPath('data.url', null)->assertJsonPath('data.link_label', null);
});

it('links only to a page that is on the site', function () {
	$rhino = Software::create(['title' => ['de' => 'Rhinoceros'], 'publish' => true]);
	$course = Course::factory()->create(['title' => ['de' => 'Rhino Einstieg'], 'slug' => ['de' => 'rhino-einstieg'], 'publish' => false]);

	expect(NewsLink::url('software:'.$rhino->uuid))->toBeNull();

	LicenceVariant::factory()->for(LicenceProduct::factory()->for($rhino), 'product')->create();
	expect(NewsLink::url('software:'.$rhino->uuid))->toBe('/de/angebote/rhinoceros')
		->and(NewsLink::url('course:'.$course->uuid))->toBeNull()
		->and(NewsLink::url('page:about'))->toBe('/de/ueber-uns')
		->and(NewsLink::url('page:nope'))->toBeNull()
		->and(NewsLink::url(null))->toBeNull();

	$course->update(['publish' => true]);
	expect(NewsLink::url('course:'.$course->uuid))->toBe('/de/angebote/kurse/rhino-einstieg');
});

it('puts the first two published beside the course dates, an arrow only where it links', function () {
	Event::factory()->create();
	News::factory()->create(['title' => ['de' => 'Erste News'], 'order' => 1, 'link' => 'page:contact']);
	News::factory()->create(['title' => ['de' => 'Entwurf'], 'order' => 2, 'publish' => false]);
	News::factory()->create(['title' => ['de' => 'Zweite News'], 'order' => 3, 'link' => null]);
	News::factory()->create(['title' => ['de' => 'Dritte News'], 'order' => 4]);

	$html = $this->get('/de')->assertOk()
		->assertSeeInOrder(['Nächste Kurstermine', 'Aktuell', 'Erste News', 'Zweite News'])
		->assertDontSee('Entwurf')
		->assertDontSee('Dritte News')
		->getContent();

	// The first is a link to its page; the second, with none, is not.
	expect($html)->toMatch('~<a\s+href="/de/kontakt"\s+class="group flex flex-col">.*?Erste News~s')
		->and($html)->toMatch('~<div\s+class="group flex flex-col">\s*<h3[^>]*>Zweite News~s');
});

it('leaves the box out when there is no news', function () {
	Event::factory()->create();

	$this->get('/de')->assertOk()->assertSee('Nächste Kurstermine')->assertDontSee('>Aktuell<', false);
});
