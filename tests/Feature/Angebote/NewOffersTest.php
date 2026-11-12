<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Course;
use App\Models\Software;

/**
 * `catalogue:offers` ([[ImportNewOffers]], [[14-angebote]] *The answers of
 * 2026-11-12*): the client's unfinished Angebote and courses, unpublished,
 * from `database/data/offers.json`, and DaVinci's course off the site.
 */
beforeEach(function () {
	foreach (['SketchUp Pro', 'Enscape', 'Veras', 'Adobe Photoshop'] as $title) {
		Software::create(['title' => ['de' => $title], 'publish' => true]);
	}

	foreach (['Architektur & Interior Design', 'AI & Neue Workflows', 'Gestaltung & Bearbeitung', 'Animation & Interaktion'] as $i => $title) {
		Category::create(['title' => ['de' => $title], 'order' => $i, 'publish' => true]);
	}

	$this->davinci = Course::factory()->create(['title' => ['de' => 'DaVinci Resolve Einführungskurs']]);
});

it('adds the new Angebote and courses unpublished, with the docs\' texts and no fee', function () {
	$this->artisan('catalogue:offers')->assertSuccessful();

	expect(Software::whereIn('title->de', ['ComfyUI', 'D5 Render', 'Figma Weave'])->where('publish', false)->count())->toBe(3);

	$d5 = Course::where('title->de', 'SketchUp & D5 Render')->first();

	expect(Course::where('publish', false)->count())->toBe(6) // the five and DaVinci
		->and($d5->subtitle)->toBe('Vom 3D-Modell zum Hingucker')
		->and($d5->fee)->toBe('0.00')
		->and($d5->facts)->toHaveCount(4)
		->and($d5->facts[3]['de'])->toBe('<p><strong>Gruppengrösse:</strong></p><p>4 bis max. 8 Personen</p>')
		->and($d5->software->pluck('title')->sort()->values()->all())->toBe(['D5 Render', 'SketchUp Pro'])
		->and($d5->categories->pluck('title')->sort()->values()->all())->toBe(['AI & Neue Workflows', 'Architektur & Interior Design'])
		->and($d5->getTranslation('information_booking', 'de'))->toContain('<a href="/de/kontakt">Melde dich gerne bei uns.</a>')
		->and($this->davinci->fresh()->publish)->toBeFalse();
});

it('leaves what exists alone on a rerun, and writes nothing on a dry run', function () {
	$this->artisan('catalogue:offers', ['--dry-run' => true])->assertSuccessful();
	expect(Course::count())->toBe(1)->and($this->davinci->fresh()->publish)->toBeTrue();

	$this->artisan('catalogue:offers')->assertSuccessful();
	Course::where('title->de', 'Enscape & Veras')->update(['fee' => '549.00']);

	$this->artisan('catalogue:offers')->assertSuccessful();
	expect(Course::count())->toBe(6)
		->and(Software::count())->toBe(7)
		->and(Course::where('title->de', 'Enscape & Veras')->value('fee'))->toBe('549.00');
});
