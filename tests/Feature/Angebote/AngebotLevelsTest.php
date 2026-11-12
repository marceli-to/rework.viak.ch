<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Course;
use App\Models\Event;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;
use App\Models\User;
use App\Support\Slug;

/**
 * The two levels of 2026-10-29 ([[14-angebote]]): a software is an Angebot
 * when it has a listed licence **or** a published course, its tag derived
 * from which; the client's five categories, on the Angebot, its licence
 * products and its courses, seeded by `catalogue:categories`.
 */
function angebot(string $title, bool $licence = false, bool $course = false): Software
{
	$software = Software::create(['title' => ['de' => $title], 'order' => 0, 'publish' => true]);

	if ($licence) {
		LicenceVariant::factory()->for(LicenceProduct::factory()->create(['software_id' => $software->id, 'title' => ['de' => "{$title} 8"], 'slug' => Slug::forTitles("{$title} 8")]), 'product')->create();
	}

	if ($course) {
		Course::factory()->create()->software()->attach($software);
	}

	return $software;
}

function categoryTitles($models): array
{
	return $models->map(fn (Category $category) => $category->getTranslation('title', 'de'))->sort()->values()->all();
}

it('puts a software on the site with a licence or a course, and tags it by which', function () {
	$both = angebot('Rhinoceros', licence: true, course: true);
	$licence = angebot('KeyShot', licence: true);
	$course = angebot('Blender', course: true);
	angebot('Substance 3D');
	$draft = angebot('Godot');
	Course::factory()->unpublished()->create()->software()->attach($draft);

	expect(Software::query()->onSite()->pluck('id')->all())->toEqualCanonicalizing([$both->id, $licence->id, $course->id])
		->and($both->offer())->toBe('Kurs & Software')
		->and($licence->offer())->toBe('Software')
		->and($course->offer())->toBe('Kurs')
		->and($draft->offer())->toBeNull();

	// A list asks once, and says the same.
	$listed = Software::query()->onSite()->withOffer()->get()->mapWithKeys(fn (Software $software) => [$software->id => $software->offer()]);
	expect($listed->all())->toBe([$both->id => 'Kurs & Software', $licence->id => 'Software', $course->id => 'Kurs']);

	$this->get('/de/angebote/blender')->assertOk()->assertDontSee('Lizenzen');
});

it('keeps a licence product\'s categories', function () {
	$admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$product = LicenceProduct::factory()->create();
	LicenceVariant::factory()->for($product, 'product')->create();
	$category = Category::create(['title' => ['de' => 'Animation & Interaktion'], 'order' => 1, 'publish' => true]);

	$form = $this->actingAs($admin)->getJson("/api/admin/licences/{$product->uuid}")->json('data');
	expect($form['categories'])->toBe([]);

	$saved = $this->putJson("/api/admin/licences/{$product->uuid}", [...$form, 'categories' => [$category->uuid]])->assertOk()->json('data');

	expect($saved['categories'])->toBe([$category->uuid])
		->and($product->categories()->pluck('categories.id')->all())->toBe([$category->id]);
});

it('puts the five in place of the legacy three, and seeds the Angebote, products and courses', function () {
	$legacy = Category::create(['title' => ['de' => '3D-Software'], 'order' => 1, 'publish' => true]);
	$viaks = Category::create(['title' => ['de' => 'Kommunikation & Moderation'], 'order' => 9, 'publish' => true]);

	$rhino = angebot('Rhinoceros', licence: true, course: true);
	$rhino->categories()->attach($legacy);
	$bongo = angebot('Bongo', licence: true);
	$vray = angebot('V-Ray');
	$sketchup = angebot('SketchUp Pro');
	$both = Course::factory()->create();
	$both->software()->attach([$vray->id, $sketchup->id]);
	$alone = Course::factory()->create(['title' => ['de' => 'Urban Sketching']]);
	$alone->categories()->attach($legacy);
	$facilitator = Course::factory()->create(['title' => ['de' => 'Visual Facilitator']]);
	$facilitator->categories()->attach($viaks);

	$this->artisan('catalogue:categories')->assertSuccessful()->expectsOutputToContain('Urban Sketching');

	expect(Category::query()->orderBy('order')->get()->map->getTranslation('title', 'de')->all())->toBe([
		'Schmuck- & Produktdesign', 'Architektur & Interior Design', 'Animation & Interaktion',
		'Gestaltung & Bearbeitung', 'AI & Neue Workflows', 'Kommunikation & Moderation',
	])
		->and(Category::withTrashed()->find($legacy->id)->trashed())->toBeTrue()
		->and(categoryTitles($rhino->categories()->get()))->toBe(['Architektur & Interior Design', 'Schmuck- & Produktdesign'])
		// A plugin is an Angebot of its own (#72) and takes the file's; a product its software's.
		->and(categoryTitles($bongo->categories()->get()))->toBe(['Animation & Interaktion', 'Schmuck- & Produktdesign'])
		->and(categoryTitles($rhino->products()->first()->categories()->get()))->toBe(['Architektur & Interior Design', 'Schmuck- & Produktdesign'])
		// A course every software's; one without software none; VIAK's own kept.
		->and(categoryTitles($both->categories()->get()))->toBe(['Architektur & Interior Design', 'Schmuck- & Produktdesign'])
		->and($alone->categories()->get())->toBeEmpty()
		->and(categoryTitles($facilitator->categories()->get()))->toBe(['Kommunikation & Moderation']);
});

it('leaves what VIAK chose alone on a rerun, and writes nothing on a dry run', function () {
	$rhino = angebot('Rhinoceros', licence: true);

	$this->artisan('catalogue:categories', ['--dry-run' => true])->assertSuccessful();
	expect(Category::query()->count())->toBe(0)
		->and($rhino->categories()->count())->toBe(0);

	$this->artisan('catalogue:categories')->assertSuccessful();
	$ai = Category::query()->where('title->de', 'AI & Neue Workflows')->first();
	$rhino->categories()->sync([$ai->id]);

	$this->artisan('catalogue:categories')->assertSuccessful();

	expect($rhino->categories()->pluck('categories.id')->all())->toBe([$ai->id])
		->and(Category::query()->count())->toBe(5);
});

it('sells an Angebot\'s licences on its licence page, each product a block, with the way back (#71)', function () {
	$rhino = angebot('Rhinoceros', licence: true);
	$product = $rhino->products()->first();
	$product->update(['description' => ['de' => '<p>Das Original.</p>']]);
	$product->manufacturer->update(['title' => ['de' => 'McNeel']]);
	$product->variants()->first()->update(['note' => ['de' => 'Nur mit einer Neulizenz.']]);
	$rhino->update([
		'licences_lead' => ['de' => 'Offizieller Schweizer Reseller.'],
		'requirements' => ['de' => '<p>Windows 11, macOS 26</p>'],
		'licences_quote' => ['de' => 'Unsicher, welche Lizenz die richtige ist?'],
	]);

	$this->get('/de/angebote/software/rhinoceros')
		->assertOk()
		->assertSeeInOrder(['Rhinoceros Lizenzen', 'Offizieller Schweizer Reseller.', 'Alles zu Rhinoceros', 'Lizenzen',
			'id="rhinoceros-8"', 'Rhinoceros 8', 'von McNeel', 'Das Original.', 'Nur mit einer Neulizenz.', 'In den Warenkorb',
			'Systemvoraussetzungen', 'Windows 11, macOS 26', '«Unsicher, welche Lizenz die richtige ist?»'], false)
		->assertSee('href="/de/angebote/rhinoceros"', false);

	// A product's page of before is its block now, one hop.
	$this->get('/de/angebote/software/rhinoceros-8')->assertRedirect('/de/angebote/software/rhinoceros#rhinoceros-8')->assertStatus(301);

	// The Angebot's row names it and links to its block.
	$this->get('/de/angebote/rhinoceros')->assertSee('href="/de/angebote/software/rhinoceros#rhinoceros-8"', false);

	expect($this->get('/sitemap.xml')->getContent())->toContain('/de/angebote/software/rhinoceros<')
		->not->toContain('/de/angebote/software/rhinoceros-8');
});

it('has no licence page for an Angebot that sells nothing', function () {
	angebot('Blender', course: true);

	$this->get('/de/angebote/software/blender')->assertNotFound();
});

it('has no page for a product that is not on the site', function () {
	$hidden = LicenceProduct::factory()->create(['title' => ['de' => 'Versteckt']]);
	LicenceVariant::factory()->for($hidden, 'product')->hidden()->create();
	$draft = LicenceProduct::factory()->create(['title' => ['de' => 'Entwurf'], 'publish' => false]);
	LicenceVariant::factory()->for($draft, 'product')->create();
	$orphan = LicenceProduct::factory()->create(['title' => ['de' => 'Verwaist']]);
	LicenceVariant::factory()->for($orphan, 'product')->create();
	$orphan->software->update(['publish' => false]);

	$this->get('/de/angebote/software/versteckt')->assertNotFound();
	$this->get('/de/angebote/software/entwurf')->assertNotFound();
	$this->get('/de/angebote/software/verwaist')->assertNotFound();
});

it('draws every date as the course page does, the course\'s name over it', function () {
	$blender = angebot('Blender');
	$course = Course::factory()->create(['title' => ['de' => 'Blender Einführungskurs'], 'slug' => ['de' => 'blender-einfuehrungskurs'], 'fee' => '899.00']);
	$course->software()->attach($blender);
	$first = Event::factory()->for($course)->create(['date' => '2026-12-03', 'registration_until' => '2026-11-19', 'fee' => '899.00']);
	$first->dates()->createMany([['date' => '2026-12-03'], ['date' => '2026-12-04']]);
	Event::factory()->for($course)->create(['date' => '2027-01-14']);

	$this->travelTo('2026-10-29');

	$html = $this->get('/de/angebote/blender')->assertOk()
		->assertSee('03. Dezember 2026')
		->assertDontSee('Anmeldung möglich bis')
		->assertSee('Details')
		->assertDontSee('Buchen')
		->assertDontSee('Zur Merkliste')
		->assertDontSee('Kurs offen, wird bestätigt')
		->assertDontSee('Lizenzen')
		->getContent();

	// Each date its own row, its name and *Details* both leading to the course.
	expect(substr_count($html, 'href="/de/angebote/kurse/blender-einfuehrungskurs"'))->toBe(4);
});

/** For dev and staging: the client's website texts brought new tags (#70). */
it('lets the file win with --sync, courses following their software', function () {
	$this->artisan('catalogue:categories')->assertSuccessful();
	$lumion = Software::create(['title' => ['de' => 'Lumion'], 'order' => 0, 'publish' => true]);
	$ai = Category::query()->where('title->de', 'AI & Neue Workflows')->first();
	$lumion->categories()->sync([$ai->id]);
	$course = Course::factory()->create();
	$course->software()->attach($lumion);
	$course->categories()->sync([$ai->id]);

	// Without --sync, VIAK's choice stands.
	$this->artisan('catalogue:categories')->assertSuccessful();
	expect(categoryTitles($lumion->categories()->get()))->toBe(['AI & Neue Workflows']);

	$this->artisan('catalogue:categories', ['--sync' => true])->assertSuccessful();

	expect(categoryTitles($lumion->categories()->get()))->toBe(['Animation & Interaktion', 'Architektur & Interior Design'])
		->and(categoryTitles($course->categories()->get()))->toBe(['Animation & Interaktion', 'Architektur & Interior Design']);
});
