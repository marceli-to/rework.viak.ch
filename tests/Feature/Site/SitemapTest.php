<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\ExpertProfile;
use App\Models\User;

/**
 * `/sitemap.xml` (`Todo.md`, *SEO*): every public page on the canonical host,
 * nothing a guest cannot open, and each URL the one its page calls canonical.
 */
function sitemapUrls(): array
{
	$response = test()->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

	return array_map('strval', simplexml_load_string($response->getContent())->xpath('//*[local-name()="loc"]'));
}

beforeEach(function () {
	$this->course = Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs'], 'slug' => ['de' => 'rhino-einstiegskurs']]);
	Course::factory()->unpublished()->create(['title' => ['de' => 'Entwurf'], 'slug' => ['de' => 'entwurf']]);

	$this->expert = User::factory()->expert()->create(['first_name' => 'Remo', 'last_name' => 'Kast']);
	ExpertProfile::factory()->for($this->expert)->create();
	$hidden = User::factory()->expert()->create(['first_name' => 'Nicht', 'last_name' => 'Sichtbar']);
	ExpertProfile::factory()->for($hidden)->create(['visible' => false]);
});

it('names the fixed pages, the published courses and the listed experts, on the canonical host', function () {
	$host = 'https://'.config('site.canonical_host');

	expect(sitemapUrls())->toEqualCanonicalizing([
		"{$host}/de",
		"{$host}/de/kurse",
		"{$host}/de/kurs/rhino-einstiegskurs",
		"{$host}/de/experten",
		"{$host}/de/experte/remo-kast/{$this->expert->uuid}",
		"{$host}/de/kontakt",
	]);
});

it('names only pages that open, each under the URL it calls canonical', function () {
	foreach (sitemapUrls() as $url) {
		$path = parse_url($url, PHP_URL_PATH);

		$this->get($path)->assertOk()->assertSee('<link rel="canonical" href="'.$url.'">', false);
	}
});

it('is named in robots.txt', function () {
	expect(file_get_contents(public_path('robots.txt')))
		->toContain('Sitemap: https://visualisierungs-akademie.ch/sitemap.xml');
});
