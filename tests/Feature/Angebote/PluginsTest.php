<?php

declare(strict_types=1);

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;
use App\Models\User;
use App\Support\Slug;

/**
 * Plugins as Angebote of their own (#72, the client 2026-11-12,
 * [[14-angebote]] *As built: step 4*): *Plugin* is the Angebot's, with the
 * programs it runs in (*Läuft in*). Its page says *Plugin für …*, its products
 * are listed under *Plugins* on each host's page, and the tiles say *Plugin*.
 * Until then (#67) plugin products sat under Rhinoceros.
 */
function angebotWithProduct(string $title, string $product, string $price, array $attributes = []): Software
{
	$software = Software::create(['title' => ['de' => $title], 'slug' => Slug::forTitles($title), 'order' => 0, 'publish' => true, ...$attributes]);

	LicenceVariant::factory()->for(LicenceProduct::factory()->create([
		'software_id' => $software->id, 'title' => ['de' => $product], 'slug' => Slug::forTitles($product),
	]), 'product')->create(['price' => $price, 'listed' => true]);

	return $software;
}

function rhinoAndBongo(): array
{
	$rhino = angebotWithProduct('Rhinoceros', 'Rhinoceros 9', '930.00');
	$bongo = angebotWithProduct('Bongo', 'Bongo 2', '280.00', ['plugin' => true]);
	$bongo->hosts()->sync([$rhino->id]);

	return [$rhino, $bongo];
}

it('lists the products of the plugins that run in an Angebot under Plugins, after its own licences', function () {
	rhinoAndBongo();

	$this->get('/de/angebote/rhinoceros')->assertOk()
		->assertSeeInOrder(['Lizenzen', 'Rhinoceros 9', 'Plugins', 'Bongo 2']);
});

it('has no Plugins section where nothing runs in it, and lists no plugin of an Angebot off the site', function () {
	[, $bongo] = rhinoAndBongo();
	$bongo->update(['publish' => false]);

	$this->get('/de/angebote/rhinoceros')->assertOk()->assertDontSee('Plugins')->assertDontSee('Bongo 2');
});

it('says on a plugin\'s page where it runs, linked, and only Plugin where it runs in many', function () {
	[, $bongo] = rhinoAndBongo();

	$this->get('/de/angebote/bongo')->assertOk()
		->assertSee('Plugin für')
		->assertSee('href="/de/angebote/rhinoceros"', false);

	$vray = angebotWithProduct('V-Ray', 'V-Ray Solo', '450.00', ['plugin' => true]);
	$this->get('/de/angebote/v-ray')->assertOk()->assertSee('Plugin')->assertDontSee('Plugin für');
});

it('prices an Angebot by its own licences only', function () {
	[$rhino, $bongo] = rhinoAndBongo();

	expect($rhino->fresh()->licenceFrom())->toBe('930.00')
		->and($bongo->fresh()->licenceFrom())->toBe('280.00');
});

it('tags a plugin Plugin on its Angebot tile and its products\' tiles', function () {
	[$rhino, $bongo] = rhinoAndBongo();

	expect($bongo->offer())->toBe('Plugin')
		->and($rhino->offer())->toBe('Software');

	$this->get('/de/angebote/software')->assertOk()
		->assertSeeInOrder(['Plugin', 'Bongo 2', 'Plugin für Rhinoceros'])
		->assertSeeInOrder(['Software', 'Rhinoceros 9', 'für Rhinoceros']);
});

it('is set on the Angebot in the dashboard, with the programs it runs in, never a plugin', function () {
	$admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	[$rhino, $bongo] = rhinoAndBongo();

	// A plugin is no host, itself least of all: not among the choices.
	$this->actingAs($admin)->putJson("/api/admin/software/{$bongo->uuid}", [
		'title' => 'Bongo', 'plugin' => true, 'publish' => true, 'hosts' => [$rhino->uuid, $bongo->uuid],
	])->assertJsonValidationErrors('hosts.1');

	$this->actingAs($admin)->putJson("/api/admin/software/{$bongo->uuid}", [
		'title' => 'Bongo', 'plugin' => true, 'publish' => true, 'hosts' => [$rhino->uuid],
	])->assertOk()
		->assertJsonPath('data.plugin', true)
		->assertJsonPath('data.hosts', [$rhino->uuid]);

	expect($bongo->fresh()->hosts->pluck('id')->all())->toBe([$rhino->id]);
});

it('imports the plugins as Angebote of their own, with where they run', function () {
	Software::create(['title' => ['de' => 'Rhinoceros'], 'order' => 0, 'publish' => true]);

	$this->artisan('licences:import')->assertSuccessful();

	$plugins = Software::where('plugin', true)->with('hosts')->get()
		->mapWithKeys(fn (Software $software) => [$software->getTranslation('title', 'de') => $software->hosts->map->getTranslation('title', 'de')->all()]);

	expect($plugins->keys()->sort()->values()->all())
		->toBe(['Bongo', 'Corona Renderer', 'Drakon', 'Enscape', 'Karamba3D', 'Lands Design', 'MatrixGold', 'Rhino.io', 'V-Ray', 'Veras', 'VisualARQ'])
		->and($plugins['Bongo'])->toBe(['Rhinoceros'])
		->and($plugins['Rhino.io'])->toBe(['CINEMA 4D'])
		->and($plugins['V-Ray'])->toBe([])
		->and(LicenceProduct::where('title->de', 'Bongo 2')->first()->software->getTranslation('title', 'de'))->toBe('Bongo');
});

/** With EDU in the shop (#70), an Ausbildungslizenz is no "ab" price (Marcel, 2026-11-09). */
it('leaves an Ausbildungslizenz out of the from-price, unless there is nothing else', function () {
	[$rhino] = rhinoAndBongo();
	$product = LicenceProduct::where('title->de', 'Rhinoceros 9')->first();
	$edu = LicenceVariant::factory()->for($product, 'product')->create(['price' => '90.00', 'listed' => true, 'education' => true]);

	expect($product->fresh()->fromPrice())->toBe('930.00')
		->and($rhino->fresh()->licenceFrom())->toBe('930.00');

	$product->variants()->whereKeyNot($edu->id)->delete();
	expect($product->fresh()->fromPrice())->toBe('90.00');
});
