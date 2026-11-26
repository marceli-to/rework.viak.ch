<?php

declare(strict_types=1);

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;

/**
 * **Systemvoraussetzungen** from Oliver's CSV ([[ImportRequirements]]):
 * article number → Angebot, once per Angebot, a heading per product where an
 * Angebot holds several.
 */
function angebotWithSkus(string $title, array $products): Software
{
	$software = Software::create(['title' => ['de' => $title], 'publish' => true, 'order' => 0]);

	foreach ($products as $product => $skus) {
		$licence = LicenceProduct::factory()->for($software)->create(['title' => ['de' => $product]]);
		foreach ($skus as $sku) {
			LicenceVariant::factory()->for($licence, 'product')->create(['sku' => $sku]);
		}
	}

	return $software;
}

it('gives an Angebot its one text, and one with several products a heading per product', function () {
	$rhino = angebotWithSkus('Rhinoceros', ['Rhino 9' => ['RHN-1003', 'RHN-1013']]);
	$vray = angebotWithSkus('V-Ray', ['V-Ray' => ['VRY-1001'], 'Chaos Vantage' => ['VRY-1007']]);

	$this->artisan('catalogue:requirements')->assertSuccessful();

	$text = $rhino->fresh()->getTranslation('requirements', 'de');
	expect($text)->toStartWith('<p><strong>Betriebssystem')
		->toContain('rhino3d.com/system_requirements')
		->not->toContain('<h3>');

	expect($vray->fresh()->getTranslation('requirements', 'de'))
		->toContain('<h3>V-Ray</h3>')
		->toContain('<h3>Chaos Vantage</h3>')
		->not->toContain('<h3>Chaos Phoenix</h3>');
});

it('keeps requirements an Angebot already has unless forced, and writes nothing on a dry run', function () {
	$rhino = angebotWithSkus('Rhinoceros', ['Rhino 9' => ['RHN-1003']]);
	$rhino->setTranslation('requirements', 'de', '<p>Von Hand</p>')->save();
	$karamba = angebotWithSkus('Karamba3D', ['Karamba3D PRO' => ['RHN-2031']]);

	$this->artisan('catalogue:requirements --dry-run')->assertSuccessful();
	expect($karamba->fresh()->getTranslation('requirements', 'de', false))->toBeEmpty();

	$this->artisan('catalogue:requirements')->assertSuccessful();
	expect($rhino->fresh()->getTranslation('requirements', 'de'))->toBe('<p>Von Hand</p>')
		->and($karamba->fresh()->getTranslation('requirements', 'de'))->toContain('Grasshopper');

	$this->artisan('catalogue:requirements --force')->assertSuccessful();
	expect($rhino->fresh()->getTranslation('requirements', 'de'))->toContain('Betriebssystem');
});
