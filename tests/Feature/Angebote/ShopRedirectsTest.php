<?php

declare(strict_types=1);

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;
use App\Support\ShopRedirects;
use App\Support\Slug;

/**
 * The old licence shop, 3d-software.ch ([[ShopRedirects]], the client's page
 * tree of 2026-11-12): every path on its hosts is one 301 to the page that
 * replaced it, on the canonical host.
 */
function rhinoInTheShop(): Software
{
	$rhino = Software::create(['title' => ['de' => 'Rhinoceros'], 'slug' => Slug::forTitles('Rhinoceros'), 'order' => 0, 'publish' => true]);

	LicenceVariant::factory()->for(LicenceProduct::factory()->create([
		'software_id' => $rhino->id, 'title' => ['de' => 'Bongo 2'], 'slug' => Slug::forTitles('Bongo 2'),
	]), 'product')->create(['sku' => 'RHN-1121', 'listed' => true]);

	return $rhino;
}

it('sends an old product page to the licence page holding its article number', function () {
	rhinoInTheShop();

	$this->get('https://www.3d-software.ch/public/Bongo-2/RHN-1121')
		->assertStatus(301)
		->assertRedirect('https://'.config('site.canonical_host').'/de/angebote/software/rhinoceros#bongo-2');
});

it('sends an article number nobody holds to its Angebot, until a product does', function () {
	$rhino = rhinoInTheShop();

	$this->get('https://3d-software.ch/public/Rhinoceros-9/RHN-1003')
		->assertRedirect('https://'.config('site.canonical_host').'/de/angebote/rhinoceros');

	LicenceVariant::factory()->for(LicenceProduct::factory()->create([
		'software_id' => $rhino->id, 'title' => ['de' => 'Rhinoceros 9'], 'slug' => Slug::forTitles('Rhinoceros 9'),
	]), 'product')->create(['sku' => 'RHN-1003', 'listed' => true]);

	$this->get('https://3d-software.ch/public/Rhinoceros-9/RHN-1003')
		->assertRedirect('https://'.config('site.canonical_host').'/de/angebote/software/rhinoceros#rhinoceros-9');
});

it('takes the old shop\'s fixed pages, and sends anything else to the software list', function () {
	$canonical = 'https://'.config('site.canonical_host');

	$this->get('https://www.3d-software.ch/')->assertRedirect($canonical.'/de/angebote/software');
	$this->get('https://www.3d-software.ch/public/Kontakt/')->assertRedirect($canonical.'/de/kontakt');
	$this->get('https://www.3d-software.ch/public/Maxwell-V5-Studio-floating/MXS-1102')->assertRedirect($canonical.'/de/angebote/software');
	$this->get('https://www.3d-software.ch/nie-gesehen?sort=price')->assertRedirect($canonical.'/de/angebote/software');
});

it('leaves the site\'s own host alone', function () {
	$this->get('/')->assertRedirect('/de');
});

it('maps every path to targets it knows', function () {
	foreach (ShopRedirects::map() as $path => $chain) {
		expect($path)->toBe(ShopRedirects::normal($path));

		foreach (explode('|', $chain) as $alternative) {
			expect($alternative)->toMatch('/^(sku|licence|software|path):\S+$/');
		}
	}
});
