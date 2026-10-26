<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Project;
use App\Models\Software;
use App\Support\Search\SearchIndex;
use Illuminate\Support\Facades\DB;

/**
 * The site search ([[15-search]]): what is indexed and when, and what a query
 * finds. Committed rows, so not under `RefreshDatabase` (`tests/Pest.php`).
 */
function search(string $query): array
{
	return app(SearchIndex::class)->search($query, 'de')->map(fn (object $hit) => "{$hit->label}: {$hit->title}")->all();
}

function rhino(): Software
{
	$software = Software::create(['title' => ['de' => 'Rhinoceros'], 'subtitle' => ['de' => 'Freiformen für Schmuck'], 'order' => 0, 'publish' => true]);
	LicenceVariant::factory()->for(LicenceProduct::factory()->for($software)->state(['title' => ['de' => 'Rhinoceros 8']]), 'product')->create();

	return $software;
}

it('finds a course, a software, its licence and a Vorhaben, by a prefix', function () {
	rhino();
	Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs'], 'slug' => ['de' => 'rhino-einstiegskurs']]);
	Project::factory()->create(['title' => ['de' => 'Objekte entwerfen'], 'text' => ['de' => '<p>Mit <strong>Rhino</strong> &amp; Grasshopper.</p>']]);

	expect(search('rhin'))->toBe([
		'Software: Rhinoceros',
		'Kurs: Rhino Einstiegskurs',
		'Lizenz: Rhinoceros 8',
		'Vorhaben: Objekte entwerfen',
	]);
});

it('indexes rich text as plain text', function () {
	Project::factory()->create(['title' => ['de' => 'Objekte entwerfen'], 'text' => ['de' => '<p>Mit <strong>Grass</strong>hopper &amp; mehr.</p>']]);

	expect(DB::table('search_entries')->value('body'))->toContain('Mit Grasshopper & mehr.')
		->and(search('grasshopper'))->toBe(['Vorhaben: Objekte entwerfen']);
});

it('drops a record that leaves the site, and takes it back when it returns', function () {
	$course = Course::factory()->create(['title' => ['de' => 'Twinmotion Einstieg']]);
	expect(search('twinmotion'))->toHaveCount(1);

	$course->update(['publish' => false]);
	expect(search('twinmotion'))->toBe([]);

	$course->update(['publish' => true]);
	$course->delete();
	expect(search('twinmotion'))->toBe([]);

	$course->restore();
	expect(search('twinmotion'))->toHaveCount(1);
});

it('drops a software and its licence when the last listed variant goes off the site', function () {
	rhino();
	expect(search('rhinoceros'))->toHaveCount(2);

	LicenceVariant::first()->update(['listed' => false]);

	expect(search('rhinoceros'))->toBe([]);
});

it('ranks a title with every word above a text that only mentions them', function () {
	Course::factory()->create(['title' => ['de' => 'Lumion Einstiegskurs'], 'subtitle' => ['de' => 'Lumion, Lumion und noch einmal Lumion, auch Lumion Pro']]);
	LicenceVariant::factory()->for(LicenceProduct::factory()->state(['title' => ['de' => 'Lumion Pro']]), 'product')->create();

	expect(search('lumion pro')[0])->toBe('Lizenz: Lumion Pro');
});

it('finds two-letter words at the start of a word only', function () {
	Course::factory()->create(['title' => ['de' => 'KI-Einstieg für Video Creator']]);
	Course::factory()->create(['title' => ['de' => 'Skizzen am Tablet'], 'subtitle' => ['de' => 'Vom Skizzieren zum Bild']]);
	Course::factory()->create(['title' => ['de' => 'SketchUp Kurs'], 'subtitle' => ['de' => 'Einstieg in 3D']]);

	expect(search('KI'))->toBe(['Kurs: KI-Einstieg für Video Creator'])
		->and(search('3d'))->toBe(['Kurs: SketchUp Kurs']);
});

it('takes nothing from the query but letters and digits', function () {
	Course::factory()->create(['title' => ['de' => 'V-Ray für Rhino']]);

	expect(SearchIndex::words('"(+V-Ray*) ~@rhino<'))->toBe(['v', 'ray', 'rhino'])
		->and(search('"(+V-Ray*) ~@rhino<'))->toBe(['Kurs: V-Ray für Rhino'])
		->and(search('+-*"()'))->toBe([]);
});

it('rebuilds the index from scratch', function () {
	rhino();
	DB::table('search_entries')->delete();

	$this->artisan('search:rebuild')->expectsOutputToContain('2 search entries written.')->assertSuccessful();
});

it('answers the header field as JSON, nothing under two characters', function () {
	rhino();

	$this->getJson('/api/search?q=rhin')
		->assertOk()
		->assertJsonPath('data.0.title', 'Rhinoceros')
		->assertJsonPath('data.0.label', 'Software')
		->assertJsonPath('data.0.lead', 'Freiformen für Schmuck')
		->assertJsonPath('data.0.url', '/de/software/rhinoceros');

	$this->getJson('/api/search?q=r')->assertOk()->assertJsonCount(0, 'data');
});

it('shows the hits as one ranked list on the result page, kept out of search engines', function () {
	rhino();
	Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs']]);

	$this->get('/de/suche?q=rhino')
		->assertOk()
		->assertSee('noindex', false)
		->assertSeeInOrder(['3 Einträge gefunden', 'Rhinoceros', 'Software', 'Rhino Einstiegskurs', 'Kurs', 'Rhinoceros 8', 'Lizenz']);

	$this->get('/de/suche?q=xyzzy')->assertOk()->assertSee('Nichts gefunden für «xyzzy»', false);
	$this->get('/de/suche')->assertOk()->assertSee('Zum Beispiel');
});

it('puts the search in the header, on every page', function () {
	$this->get('/de/kontakt')->assertOk()->assertSee('aria-label="Suche"', false)->assertSee('id="menu-search"', false);
});
