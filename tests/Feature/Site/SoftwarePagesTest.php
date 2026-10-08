<?php

declare(strict_types=1);

use App\Enums\LicenceAccess;
use App\Enums\LicenceType;
use App\Models\Category;
use App\Models\Course;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Manufacturer;
use App\Models\Software;

/**
 * The software list and a software's page ([[05-licences]]), drawn as the
 * course pages are. What the server owes the browser: only software with
 * something to order, the filter's facets read off the listed licences, the
 * first paint already filtered, and each product with its listed licences.
 */
function shopSoftware(string $title, array $variants = [[]], bool $publish = true): Software
{
	$software = Software::create(['title' => ['de' => $title], 'publish' => $publish]);
	$product = LicenceProduct::factory()->create(['software_id' => $software->id, 'title' => ['de' => "{$title} Pro"]]);

	foreach ($variants as $variant) {
		LicenceVariant::factory()->create(['licence_product_id' => $product->id, ...$variant]);
	}

	return $software;
}

it('lists only software with a licence to order', function () {
	shopSoftware('Rhinoceros');
	shopSoftware('Versteckt', [['listed' => false]]);
	shopSoftware('Entwurf', publish: false);
	Software::create(['title' => ['de' => 'SketchUp'], 'publish' => true]);

	$this->get('/de/software')
		->assertOk()
		->assertSee('Rhinoceros')
		->assertDontSee('Versteckt')
		->assertDontSee('Entwurf')
		->assertDontSee('SketchUp');

	$this->get('/de/software/versteckt')->assertNotFound();
	$this->get('/de/software/sketchup')->assertNotFound();
});

it('filters by what the licences say, a both-ways licence counting as both', function () {
	$network = shopSoftware('V-Ray', [['access' => LicenceAccess::Floating, 'licence_type' => LicenceType::Subscription]]);
	$either = shopSoftware('Rhinoceros', [['access' => LicenceAccess::Either, 'licence_type' => LicenceType::Perpetual, 'platforms' => ['windows']]]);
	$single = shopSoftware('Enscape', [['access' => LicenceAccess::Named]]);

	$page = $this->get('/de/software?access=floating')->assertOk();
	$facets = $page->viewData('filter')->facets();

	expect($facets[$either->uuid]['access'])->toBe(['named', 'floating'])
		->and($facets[$either->uuid]['platform'])->toBe(['windows'])
		->and($page->viewData('filter')->matching())->toEqualCanonicalizing([$network->uuid, $either->uuid]);

	expect($this->get('/de/software?licence_type=perpetual')->viewData('filter')->matching())->toBe([$either->uuid]);
});

it('offers a maker and a category only where some software carries it', function () {
	$rhino = shopSoftware('Rhinoceros');
	$category = Category::create(['title' => ['de' => 'Architektur'], 'order' => 1, 'publish' => true]);
	Category::create(['title' => ['de' => 'Animation'], 'order' => 2, 'publish' => true]);
	$rhino->categories()->attach($category);
	$hidden = LicenceProduct::factory()->create(['software_id' => $rhino->id, 'manufacturer_id' => Manufacturer::create(['title' => ['de' => 'Gemvision']])->id]);
	LicenceVariant::factory()->hidden()->create(['licence_product_id' => $hidden->id]);

	$options = $this->get('/de/software')->viewData('filter')->options();

	expect($options['category'])->toBe([$category->uuid => 'Architektur'])
		->and($options['manufacturer'])->not->toContain('Gemvision');
});

it('shows every product with its listed licences, and the courses that teach it', function () {
	$rhino = shopSoftware('Rhinoceros', [
		['title' => ['de' => 'Vollversion'], 'licence_type' => LicenceType::Perpetual, 'access' => LicenceAccess::Either, 'price' => '940.00'],
		['title' => ['de' => 'EDU'], 'listed' => false],
	]);
	$plugin = LicenceProduct::factory()->create(['software_id' => $rhino->id, 'title' => ['de' => 'Bongo 2']]);
	LicenceVariant::factory()->demo()->create(['licence_product_id' => $plugin->id]);
	$course = Course::factory()->create(['publish' => true, 'title' => ['de' => 'Rhino Einstiegskurs']]);
	$course->software()->attach($rhino);

	$this->get('/de/software/rhinoceros')
		->assertOk()
		->assertSee('Rhinoceros Pro')
		->assertSee('Vollversion, Dauerlizenz, Einzelplatz oder Netzwerk')
		->assertSee('CHF 940.00')
		->assertDontSee('EDU')
		->assertSee('Bongo 2')
		->assertSee('kostenlos')
		->assertSee('Rhino Einstiegskurs');
});

it('keeps a renamed software on its URL', function () {
	$software = shopSoftware('V-Ray');
	$software->update(['title' => ['de' => 'V-Ray 7']]);

	$this->get('/de/software/v-ray')->assertOk()->assertSee('V-Ray 7');
});
