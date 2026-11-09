<?php

declare(strict_types=1);

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Manufacturer;
use App\Models\Software;
use App\Models\User;
use App\Support\Slug;

/**
 * Plugins (#67, the client 2026-11-03, [[14-angebote]]): a licence product
 * marked *Plugin* stays under the Angebot it runs in, listed there under
 * *Plugins* apart from the Angebot's own *Lizenzen*, and tagged on the
 * Software list's tile.
 */
function rhinoWithBongo(): Software
{
	$rhino = Software::create(['title' => ['de' => 'Rhinoceros'], 'slug' => Slug::forTitles('Rhinoceros'), 'order' => 0, 'publish' => true]);

	LicenceVariant::factory()->for(LicenceProduct::factory()->create([
		'software_id' => $rhino->id, 'title' => ['de' => 'Rhinoceros 8'], 'slug' => Slug::forTitles('Rhinoceros 8'),
	]), 'product')->create(['price' => '550.00', 'listed' => true]);

	LicenceVariant::factory()->for(LicenceProduct::factory()->plugin()->create([
		'software_id' => $rhino->id, 'title' => ['de' => 'Bongo 2'], 'slug' => Slug::forTitles('Bongo 2'),
	]), 'product')->create(['price' => '280.00', 'listed' => true]);

	return $rhino;
}

it('lists an Angebot\'s plugins under Plugins, after its own licences', function () {
	rhinoWithBongo();

	$this->get('/de/angebote/rhinoceros')->assertOk()
		->assertSeeInOrder(['Lizenzen', 'Rhinoceros 8', 'Plugins', 'Bongo 2']);
});

it('has no Plugins section without a plugin', function () {
	rhinoWithBongo();
	LicenceProduct::where('title->de', 'Bongo 2')->update(['plugin' => false]);

	$this->get('/de/angebote/rhinoceros')->assertOk()
		->assertSeeInOrder(['Lizenzen', 'Bongo 2'])
		->assertDontSee('Plugins');
});

it('prices an Angebot by its own licences before its plugins', function () {
	$rhino = rhinoWithBongo();

	expect($rhino->fresh()->licenceFrom())->toBe('550.00');

	// With nothing of its own, a plugin's price is the Angebot's.
	LicenceProduct::where('title->de', 'Rhinoceros 8')->update(['publish' => false]);
	expect($rhino->fresh()->licenceFrom())->toBe('280.00');
});

it('tags a tile on the Software list Software or Plugin', function () {
	rhinoWithBongo();

	$this->get('/de/angebote/software')->assertOk()
		->assertSeeInOrder(['Plugin', 'Bongo 2', 'Plugin für Rhinoceros'])
		->assertSeeInOrder(['Software', 'Rhinoceros 8', 'für Rhinoceros']);
});

it('is set in the dashboard\'s product form', function () {
	$admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$software = Software::create(['title' => ['de' => 'Rhinoceros'], 'order' => 1, 'publish' => true]);
	$maker = Manufacturer::create(['title' => ['de' => 'Bongo'], 'order' => 1, 'publish' => true]);

	$uuid = $this->actingAs($admin)->postJson('/api/admin/licences', [
		'software' => $software->uuid, 'manufacturer' => $maker->uuid, 'title' => 'Bongo 2',
		'description' => '', 'hosts' => [], 'plugin' => true, 'publish' => true,
	])->assertCreated()->assertJsonPath('data.plugin', true)->json('data.uuid');

	expect(LicenceProduct::where('uuid', $uuid)->first()->plugin)->toBeTrue();
});

it('marks the six plugins under Rhinoceros on import', function () {
	$this->artisan('licences:import')->assertSuccessful();

	expect(LicenceProduct::where('plugin', true)->get()->map(fn (LicenceProduct $product) => $product->getTranslation('title', 'de'))->sort()->values()->all())
		->toBe(['Bongo 2', 'Drakon', 'Karamba3D PRO', 'Lands Design 6', 'MatrixGold', 'VisualARQ 3']);
});

/** With EDU in the shop (#70), an Ausbildungslizenz is no "ab" price (Marcel, 2026-11-09). */
it('leaves an Ausbildungslizenz out of the from-price, unless there is nothing else', function () {
	$rhino = rhinoWithBongo();
	$product = LicenceProduct::where('title->de', 'Rhinoceros 8')->first();
	$edu = LicenceVariant::factory()->for($product, 'product')->create(['price' => '90.00', 'listed' => true, 'education' => true]);

	expect($product->fresh()->fromPrice())->toBe('550.00')
		->and($rhino->fresh()->licenceFrom())->toBe('550.00');

	$product->variants()->whereKeyNot($edu->id)->delete();
	expect($product->fresh()->fromPrice())->toBe('90.00');
});
