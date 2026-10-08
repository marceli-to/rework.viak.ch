<?php

declare(strict_types=1);

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Manufacturer;
use App\Models\Software;
use App\Models\User;

/**
 * *Lizenzen* — `/api/admin/licences` ([[05-licences]]): the catalogue VIAK
 * keeps itself, a product and the variants of its dropdown.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$this->software = Software::create(['title' => ['de' => 'V-Ray'], 'order' => 1, 'publish' => true]);
	$this->maker = Manufacturer::create(['title' => ['de' => 'Chaos'], 'order' => 1, 'publish' => true]);
});

function licenceVariant(array $overrides = []): array
{
	return [
		'uuid' => null,
		'title' => 'Solo, named',
		'sku' => 'VRY-1001',
		'price' => '450',
		'licence_type' => 'subscription',
		'access' => 'named',
		'platforms' => ['windows', 'macos'],
		'note' => '',
		'min_quantity' => '',
		'listed' => true,
		...$overrides,
	];
}

function licencePayload(array $overrides = []): array
{
	return [
		'software' => test()->software->uuid,
		'manufacturer' => test()->maker->uuid,
		'title' => 'V-Ray',
		'description' => '',
		'hosts' => '',
		'three_years_on_request' => true,
		'publish' => true,
		'variants' => [licenceVariant()],
		...$overrides,
	];
}

it('keeps the catalogue to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/licences')->assertForbidden();
	$this->actingAs(User::factory()->expert()->create())->postJson('/api/admin/licences', licencePayload())->assertForbidden();
});

it('creates a product with its variants, the price net to the centime', function () {
	$uuid = $this->actingAs($this->admin)->postJson('/api/admin/licences', licencePayload(['variants' => [
		licenceVariant(),
		licenceVariant(['title' => 'Demoversion', 'sku' => 'VRY-1101', 'price' => '0', 'licence_type' => null]),
	]]))
		->assertCreated()
		->assertJsonPath('data.variants.0.price', '450.00')
		->assertJsonPath('data.variants.1.licence_type', null)
		->assertJsonPath('data.from', '450.00')
		->json('data.uuid');

	$product = LicenceProduct::where('uuid', $uuid)->first();

	expect($product->getTranslations('slug'))->toBe(['de' => 'v-ray', 'en' => 'v-ray'])
		->and($product->three_years_on_request)->toBeTrue()
		->and($product->variants->pluck('order')->all())->toBe([1, 2]);
});

it('splits the host software into a list', function () {
	$this->actingAs($this->admin)->postJson('/api/admin/licences', licencePayload(['hosts' => 'Rhino, Archicad,, Cinema 4D ']))
		->assertJsonPath('data.hosts', 'Rhino, Archicad, Cinema 4D');

	expect(LicenceProduct::first()->hosts)->toBe(['Rhino', 'Archicad', 'Cinema 4D']);
});

it('updates the variants it is sent back, adds new ones and drops the rest', function () {
	$product = LicenceProduct::factory()->create();
	$keep = LicenceVariant::factory()->for($product, 'product')->create(['sku' => 'A-1']);
	$drop = LicenceVariant::factory()->for($product, 'product')->create(['sku' => 'A-2']);

	$this->actingAs($this->admin)->putJson("/api/admin/licences/{$product->uuid}", licencePayload(['variants' => [
		licenceVariant(['title' => 'Neu', 'sku' => 'A-3']),
		licenceVariant(['uuid' => $keep->uuid, 'title' => 'Geändert', 'sku' => 'A-1', 'price' => '99.5']),
	]]))->assertOk();

	expect($keep->refresh())->title->toBe('Geändert')->price->toBe('99.50')->order->toBe(2)
		->and(LicenceVariant::find($drop->id))->toBeNull()
		->and(LicenceVariant::withTrashed()->find($drop->id)->trashed())->toBeTrue()
		->and($product->variants()->pluck('sku')->all())->toBe(['A-3', 'A-1']);
});

it('refuses an article number another product has, or one twice', function () {
	LicenceVariant::factory()->create(['sku' => 'VRY-1001']);

	$this->actingAs($this->admin)->postJson('/api/admin/licences', licencePayload())
		->assertJsonPath('errors', fn (array $errors) => $errors['variants.0.sku'] === ['Diese Artikelnummer hat bereits eine andere Lizenz.']);

	$this->postJson('/api/admin/licences', licencePayload(['variants' => [licenceVariant(['sku' => 'X-1']), licenceVariant(['sku' => 'X-1'])]]))
		->assertJsonValidationErrors('variants.0.sku');
});

it('lets a product keep its own article numbers on save', function () {
	$product = LicenceProduct::factory()->create();
	$variant = LicenceVariant::factory()->for($product, 'product')->create(['sku' => 'VRY-1001']);

	$this->actingAs($this->admin)
		->putJson("/api/admin/licences/{$product->uuid}", licencePayload(['variants' => [licenceVariant(['uuid' => $variant->uuid])]]))
		->assertOk();
});

it('asks for at least one variant, and a price for each', function () {
	$this->actingAs($this->admin)->postJson('/api/admin/licences', licencePayload(['variants' => []]))
		->assertJsonPath('errors.variants.0', 'Eine Lizenz braucht mindestens eine Variante.');

	$this->postJson('/api/admin/licences', licencePayload(['variants' => [licenceVariant(['price' => ''])]]))
		->assertJsonValidationErrors('variants.0.price');
});

it('marks a product with no variant on the site as one VIAK enters by hand', function () {
	$product = LicenceProduct::factory()->create();
	LicenceVariant::factory()->for($product, 'product')->hidden()->create();

	$this->actingAs($this->admin)->getJson("/api/admin/licences/{$product->uuid}")
		->assertJsonPath('data.listed', false)
		->assertJsonPath('data.from', null);
});

it('sends back exactly what it loads', function () {
	$product = LicenceProduct::factory()->create(['hosts' => ['Rhino', 'Maya']]);
	LicenceVariant::factory()->for($product, 'product')->create(['min_quantity' => 3, 'note' => ['de' => 'Mindestens drei']]);
	LicenceVariant::factory()->for($product, 'product')->demo()->create(['order' => 2]);

	$form = $this->actingAs($this->admin)->getJson("/api/admin/licences/{$product->uuid}")->json('data');

	expect($this->putJson("/api/admin/licences/{$product->uuid}", $form)->assertOk()->json('data'))->toBe($form);
});

it('deletes a product and its variants softly', function () {
	$product = LicenceProduct::factory()->create();
	$variant = LicenceVariant::factory()->for($product, 'product')->create();

	$this->actingAs($this->admin)->deleteJson("/api/admin/licences/{$product->uuid}")->assertNoContent();

	expect(LicenceProduct::withTrashed()->find($product->id)->trashed())->toBeTrue()
		->and(LicenceVariant::withTrashed()->find($variant->id)->trashed())->toBeTrue();
});
