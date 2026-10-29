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
	$bongo = LicenceProduct::factory()->create(['software_id' => $rhino->id, 'title' => ['de' => 'Bongo 2']]);
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
		// The product the file names takes the file's; the others their software's.
		->and(categoryTitles($bongo->categories()->get()))->toBe(['Animation & Interaktion', 'Schmuck- & Produktdesign'])
		->and(categoryTitles($rhino->products()->where('id', '!=', $bongo->id)->first()->categories()->get()))->toBe(['Architektur & Interior Design', 'Schmuck- & Produktdesign'])
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

it('sells a licence product on its own page, with the way back to its Angebot', function () {
	$rhino = angebot('Rhinoceros', licence: true);
	$product = $rhino->products()->first();
	$product->update(['features' => ['de' => "Kauflizenz, unbefristet\nWindows und Mac"]]);
	$product->manufacturer->update(['title' => ['de' => 'McNeel']]);

	$this->get('/de/angebote/software/rhinoceros-8')
		->assertOk()
		->assertSee('Rhinoceros 8')
		->assertSee('von McNeel')
		->assertSee('Kauflizenz, unbefristet')
		->assertSee('In den Warenkorb')
		->assertSee('Alles zu Rhinoceros')
		->assertSee('href="/de/angebote/rhinoceros"', false);

	// The Angebot's card names it and links here.
	$this->get('/de/angebote/rhinoceros')->assertSee('href="/de/angebote/software/rhinoceros-8"', false);

	expect($this->get('/sitemap.xml')->getContent())->toContain('/de/angebote/software/rhinoceros-8');
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

it('gives a course row its next date, its deadline while ahead, and how many more dates there are', function () {
	$blender = angebot('Blender');
	$course = Course::factory()->create(['title' => ['de' => 'Blender Einführungskurs'], 'fee' => '899.00']);
	$course->software()->attach($blender);
	$next = Event::factory()->for($course)->create(['date' => '2026-12-03', 'registration_until' => '2026-11-19', 'fee' => '899.00']);
	$next->dates()->createMany([['date' => '2026-12-03'], ['date' => '2026-12-04']]);
	Event::factory()->for($course)->create(['date' => '2027-01-14']);
	$late = Course::factory()->create(['title' => ['de' => 'Blender Renderingkurs']]);
	$late->software()->attach($blender);
	Event::factory()->for($late)->create(['date' => '2026-11-05', 'registration_until' => '2026-10-22']);

	$this->travelTo('2026-10-29');

	$this->get('/de/angebote/blender')
		->assertOk()
		->assertSee('2 Tage')
		->assertSee('3. bis 4. Dezember 2026')
		->assertSee('Anmeldung möglich bis 19.11.2026')
		->assertSee('1 weiteres Datum')
		->assertDontSee('22.10.2026')
		->assertSee('Anmelden')
		->assertDontSee('Lizenzen');
});
