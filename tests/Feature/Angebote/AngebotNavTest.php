<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Course;
use App\Models\Event;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;
use App\Support\Slug;

/**
 * Step 4 of [[14-angebote]] (Marcel, 2026-10-29): the catalogue under
 * `/de/angebote`, *Alle Angebote* by category, the switch to Kurse and
 * Software, the menu saying *Angebote* alone, and the way back from a course
 * to its Angebot.
 */
function navAngebot(string $title, bool $licence = false, bool $course = false): Software
{
	$software = Software::create(['title' => ['de' => $title], 'order' => 0, 'publish' => true]);

	if ($licence) {
		LicenceVariant::factory()->for(LicenceProduct::factory()->create(['software_id' => $software->id, 'title' => ['de' => "{$title} 8"], 'slug' => Slug::forTitles("{$title} 8")]), 'product')->create();
	}

	if ($course) {
		Course::factory()->create(['title' => ['de' => "{$title} Einstiegskurs"], 'slug' => Slug::forTitles("{$title} Einstiegskurs")])->software()->attach($software);
	}

	return $software;
}

it('sorts every Angebot and every course without one into its categories, the rest under Weitere', function () {
	$architecture = Category::create(['title' => ['de' => 'Architektur & Interior Design'], 'order' => 2, 'publish' => true]);
	$jewellery = Category::create(['title' => ['de' => 'Schmuck- & Produktdesign'], 'order' => 1, 'publish' => true]);
	Category::create(['title' => ['de' => 'AI & Neue Workflows'], 'order' => 5, 'publish' => true]);

	$rhino = navAngebot('Rhinoceros', licence: true, course: true);
	$rhino->categories()->attach([$architecture->id, $jewellery->id]);
	navAngebot('KeyShot', licence: true)->categories()->attach($jewellery);
	navAngebot('Blender', course: true);
	Course::factory()->create(['title' => ['de' => 'Urban Sketching'], 'slug' => ['de' => 'urban-sketching']]);
	navAngebot('Unsichtbar');

	$sections = $this->get('/de/angebote')->assertOk()
		->assertSee('href="/de/angebote/rhinoceros"', false)
		->assertSee('href="/de/angebote/kurse/urban-sketching"', false)
		->assertDontSee('Unsichtbar')
		->viewData('sections');

	$titles = fn (int $i) => $sections[$i]['tiles']->map(fn (array $tile) => "{$tile['title']}: {$tile['kind']}")->all();

	// In the categories' order, empty ones left out; a tool under each of its own.
	expect($sections->pluck('title')->all())->toBe(['Schmuck- & Produktdesign', 'Architektur & Interior Design', 'Weitere'])
		->and($titles(0))->toBe(['KeyShot: Software', 'Rhinoceros: Kurs & Software'])
		->and($titles(1))->toBe(['Rhinoceros: Kurs & Software'])
		->and($titles(2))->toBe(['Blender: Kurs', 'Urban Sketching: Kurs']);

	// A course of an Angebot is not a tile of its own.
	expect(collect($sections)->flatMap->tiles->pluck('title'))->not->toContain('Rhinoceros Einstiegskurs');
});

it('puts the switch above the filter on Kurse and Software, and links them in Alle Angebote\'s text instead', function () {
	foreach (['/de/angebote/kurse' => 'Kurse', '/de/angebote/software' => 'Software'] as $url => $current) {
		$html = $this->get($url)->assertOk()->getContent();
		$column = mb_substr($html, mb_strpos($html, 'aria-label="Angebote"'));

		expect($column)->toMatch('#aria-current="page"\s+class="block w-full text-lg hover:text-teal font-bold">\s*'.$current.'\s*</a>#')
			->and(mb_strpos($column, '>Filter</h2>'))->toBeGreaterThan(0);
	}

	$this->get('/de/angebote')->assertOk()
		->assertDontSee('aria-label="Angebote"', false)
		->assertSee('<a href="/de/angebote/kurse">Kurse</a> und <a href="/de/angebote/software">Software</a>', false);
});

it('says Angebote in the menu, lit on every catalogue page, with Kurse and Software under it on a phone', function () {
	navAngebot('Rhinoceros', licence: true, course: true);

	$home = $this->get('/de')->getContent();
	$desktop = mb_substr($home, mb_strpos($home, 'aria-label="Hauptnavigation"'), 2500);

	expect($desktop)->toContain('href="/de/angebote"')
		->and($desktop)->not->toContain('href="/de/angebote/kurse"')
		->and($desktop)->not->toContain('href="/de/angebote/software"')
		->and($home)->toContain('min-h-40 items-center justify-end text-2xl');

	foreach (['/de/angebote', '/de/angebote/kurse', '/de/angebote/software', '/de/angebote/rhinoceros', '/de/angebote/software/rhinoceros-8', '/de/angebote/kurse/rhinoceros-einstiegskurs'] as $url) {
		$this->get($url)->assertOk()->assertSee('class="hover:text-teal text-teal">'."\n\t\t\t\t\t\t\t\tAngebote", false);
	}
});

it('names no Angebot on a course page (Marcel, 2026-10-30)', function () {
	$sketchup = navAngebot('SketchUp', course: true);
	$sketchup->courses()->first()->software()->attach(navAngebot('V-Ray', licence: true));

	$this->get('/de/angebote/kurse/sketchup-einstiegskurs')
		->assertOk()
		->assertDontSee('Alles zu');
});

it('keeps the lists\' names out of an Angebot\'s slug, and the prototype\'s URLs landing', function () {
	expect(Software::create(['title' => ['de' => 'Software']])->getTranslation('slug', 'de'))->toBe('software-2')
		->and(Software::create(['title' => ['de' => 'Kurse']])->getTranslation('slug', 'de'))->toBe('kurse-2');

	$this->get('/de/software')->assertRedirect('/de/angebote/software')->assertStatus(301);
	$this->get('/de/software?access=named')->assertRedirect('/de/angebote/software?access=named');
	$this->get('/de/software/rhinoceros')->assertRedirect('/de/angebote/rhinoceros')->assertStatus(301);
});

it('says Kurs ab the bookable price, else the course fee with Daten auf Anfrage, and Lizenz ab or the note', function () {
	$this->travelTo('2026-10-29');
	$twinmotion = navAngebot('Twinmotion', licence: true, course: true);
	$twinmotion->courses()->first()->update(['fee' => '499.00']);

	expect($twinmotion->courseFrom())->toBe(['price' => '499.00', 'dated' => false]);

	Event::factory()->for($twinmotion->courses()->first())->create(['date' => '2026-12-01', 'fee' => '590.00']);
	expect($twinmotion->courseFrom())->toBe(['price' => '590.00', 'dated' => true]);

	$keyshot = navAngebot('KeyShot', licence: true);
	expect($keyshot->courseFrom())->toBeNull();

	$after = navAngebot('After Effects', course: true);
	$after->update(['featured' => true, 'licence_note' => ['de' => 'Lizenz direkt bei Adobe']]);
	$after->courses()->first()->update(['fee' => '989.00']);

	$this->get('/de')
		->assertSee('Lizenz direkt bei Adobe')
		->assertSee('Kurs ab CHF 989.00')
		->assertSee('Daten auf Anfrage');
});
