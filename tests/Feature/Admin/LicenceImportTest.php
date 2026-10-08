<?php

declare(strict_types=1);

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Manufacturer;
use App\Models\Software;

/**
 * `licences:import` ([[ImportLicences]], [[05-licences]]): the client's list,
 * grouped, from `database/data/licences.json`.
 */
it('imports the whole catalogue into the course software it already has', function () {
	$rhino = Software::create(['title' => ['de' => 'Rhinoceros'], 'order' => 0, 'publish' => true]);

	$this->artisan('licences:import')->assertSuccessful();

	expect(LicenceVariant::count())->toBe(107)
		->and(LicenceVariant::where('listed', false)->count())->toBe(13)
		->and(LicenceVariant::where('price', 0)->count())->toBe(10)
		->and(Manufacturer::count())->toBe(15)
		->and(LicenceProduct::where('title->de', 'Rhinoceros 8')->first()->software_id)->toBe($rhino->id)
		->and(Software::where('title->de', 'Rhinoceros')->count())->toBe(1);
});

it('carries the notes, minimums and host lists onto their places', function () {
	$this->artisan('licences:import')->assertSuccessful();

	$teams = LicenceVariant::where('sku', 'C4D-1111')->first();
	$bundle = LicenceVariant::where('sku', 'MXS-1201')->first();

	expect($teams->min_quantity)->toBe(3)
		->and($teams->product->title)->toBe('Cinema 4D')
		->and($bundle->note)->toBe('5 Rendernodes, nur zusammen mit einer Neulizenz')
		->and(LicenceProduct::where('title->de', 'Maxwell V5')->first()->hosts)->toContain('Archicad')
		->and(LicenceProduct::where('title->de', 'V-Ray')->first()->three_years_on_request)->toBeTrue()
		->and(LicenceVariant::where('sku', 'VRY-2203')->first()->product->title)->toBe('V-Ray Render Node');
});

it('leaves what VIAK has edited alone on a rerun, unless told to refresh', function () {
	$this->artisan('licences:import')->assertSuccessful();
	LicenceVariant::where('sku', 'RHN-1002')->update(['price' => '999.00']);

	$this->artisan('licences:import')->assertSuccessful();
	expect(LicenceVariant::count())->toBe(107)
		->and(LicenceVariant::where('sku', 'RHN-1002')->value('price'))->toBe('999.00');

	$this->artisan('licences:import', ['--refresh' => true])->assertSuccessful();
	expect(LicenceVariant::where('sku', 'RHN-1002')->value('price'))->toBe('940.00');
});
