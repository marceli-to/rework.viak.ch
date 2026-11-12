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

	// 107 until the list of 2026-11-12: Maxwell's 13 gone, Rhino 9's two and Drakon's demo added.
	expect(LicenceVariant::count())->toBe(97)
		// Rhino 8's two kept hidden, the three EDU hidden again (#75).
		->and(LicenceVariant::where('listed', false)->count())->toBe(15)
		->and(LicenceVariant::where('education', true)->count())->toBe(12)
		->and(LicenceVariant::where('price', 0)->count())->toBe(11)
		->and(Manufacturer::count())->toBe(15)
		->and(LicenceVariant::where('sku', 'RHN-1013')->value('compare_price'))->toBe('550.00')
		->and(LicenceProduct::where('title->de', 'Rhinoceros 9')->first()->software_id)->toBe($rhino->id)
		->and(Software::where('title->de', 'Maxwell Render')->exists())->toBeFalse()
		->and(Software::where('title->de', 'Rhinoceros')->count())->toBe(1);
});

it('carries the notes, minimums and host lists onto their places', function () {
	$this->artisan('licences:import')->assertSuccessful();

	$teams = LicenceVariant::where('sku', 'C4D-1111')->first();
	$plus = LicenceVariant::where('sku', 'RFL-1011')->first();

	expect($teams->min_quantity)->toBe(3)
		->and($teams->product->title)->toBe('Cinema 4D')
		->and($plus->note)->toBe('Inkl. 5 Simnodes')
		->and(LicenceProduct::where('title->de', 'RealFlow Plugin')->first()->hostNames())->toContain('3ds Max', 'Maya')
		->and(Software::where('title->de', 'Maya')->first()->publish)->toBeFalse()
		->and(LicenceProduct::where('title->de', 'V-Ray')->first()->three_years_on_request)->toBeTrue()
		->and(LicenceVariant::where('sku', 'VRY-2203')->first()->product->title)->toBe('V-Ray Render Node');
});

it('leaves what VIAK has edited alone on a rerun, unless told to refresh', function () {
	$this->artisan('licences:import')->assertSuccessful();
	LicenceVariant::where('sku', 'RHN-1002')->update(['price' => '999.00']);

	$this->artisan('licences:import')->assertSuccessful();
	expect(LicenceVariant::count())->toBe(97)
		->and(LicenceVariant::where('sku', 'RHN-1002')->value('price'))->toBe('999.00');

	$this->artisan('licences:import', ['--refresh' => true])->assertSuccessful();
	expect(LicenceVariant::where('sku', 'RHN-1002')->value('price'))->toBe('940.00');
});
