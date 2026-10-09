<?php

declare(strict_types=1);

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Manufacturer;
use App\Models\Software;
use App\Models\User;

/**
 * *Software* — `/api/admin/licences` ([[05-licences]]): the catalogue VIAK
 * keeps itself. The product here; its variants in [[LicenceVariantFormTest]].
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$this->software = Software::create(['title' => ['de' => 'V-Ray'], 'order' => 1, 'publish' => true]);
	$this->maker = Manufacturer::create(['title' => ['de' => 'Chaos'], 'order' => 1, 'publish' => true]);
});

function licencePayload(array $overrides = []): array
{
	return [
		'software' => test()->software->uuid,
		'manufacturer' => test()->maker->uuid,
		'title' => 'V-Ray',
		'description' => '',
		'hosts' => [],
		'three_years_on_request' => true,
		'publish' => true,
		...$overrides,
	];
}

it('keeps the catalogue to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/licences')->assertForbidden();
	$this->actingAs(User::factory()->expert()->create())->postJson('/api/admin/licences', licencePayload())->assertForbidden();
});

it('creates a product at the end of its group, its slug from the title', function () {
	LicenceProduct::factory()->create(['software_id' => $this->software->id, 'order' => 4]);

	$uuid = $this->actingAs($this->admin)->postJson('/api/admin/licences', licencePayload(['title' => 'Chaos Vantage']))
		->assertCreated()
		->assertJsonPath('data.listed', false)
		->json('data.uuid');

	$product = LicenceProduct::where('uuid', $uuid)->first();

	expect($product->getTranslations('slug'))->toBe(['de' => 'chaos-vantage', 'en' => 'chaos-vantage'])
		->and($product->order)->toBe(5)
		->and($product->three_years_on_request)->toBeTrue();
});

it('picks the host software from the Software list', function () {
	$rhino = Software::create(['title' => ['de' => 'Rhinoceros']]);
	$archicad = Software::create(['title' => ['de' => 'Archicad']]);

	$this->actingAs($this->admin)->postJson('/api/admin/licences', licencePayload(['hosts' => [$rhino->uuid, $archicad->uuid]]))
		->assertJsonPath('data.hosts', [$rhino->uuid, $archicad->uuid]);

	expect(LicenceProduct::first()->hostNames())->toBe(['Archicad', 'Rhinoceros']);

	$this->postJson('/api/admin/licences', licencePayload(['hosts' => ['Rhino']]))->assertJsonValidationErrors('hosts.0');
});

it('keeps a software that a plugin runs in', function () {
	$maya = Software::create(['title' => ['de' => 'Maya']]);
	LicenceProduct::factory()->create()->hosts()->attach($maya);

	$this->actingAs($this->admin)->deleteJson("/api/admin/software/{$maya->uuid}")->assertStatus(422);
});

it('keeps the URL and the variants when the product is saved', function () {
	$product = LicenceProduct::factory()->create(['title' => ['de' => 'Alt'], 'slug' => ['de' => 'alt']]);
	LicenceVariant::factory()->for($product, 'product')->count(2)->create();

	$this->actingAs($this->admin)->putJson("/api/admin/licences/{$product->uuid}", licencePayload(['title' => 'Neu']))->assertOk();

	expect($product->refresh()->getTranslation('slug', 'de'))->toBe('alt')
		->and($product->variants()->count())->toBe(2);
});

it('lists the cheapest listed price, and marks a product with nothing on the site', function () {
	$product = LicenceProduct::factory()->create();
	LicenceVariant::factory()->for($product, 'product')->demo()->create();
	LicenceVariant::factory()->for($product, 'product')->create(['price' => '680.00']);
	LicenceVariant::factory()->for($product, 'product')->hidden()->create(['price' => '90.00']);
	$hidden = LicenceProduct::factory()->create();
	LicenceVariant::factory()->for($hidden, 'product')->hidden()->create();

	$rows = collect($this->actingAs($this->admin)->getJson('/api/admin/licences')->json('data'))->keyBy('uuid');

	expect($rows[$product->uuid])->toMatchArray(['from' => '680.00', 'listed' => true])
		->and($rows[$hidden->uuid])->toMatchArray(['from' => null, 'listed' => false]);
});

it('sends back exactly what it loads', function () {
	$product = LicenceProduct::factory()->create();
	$product->hosts()->attach([Software::create(['title' => ['de' => 'Rhinoceros']])->id, Software::create(['title' => ['de' => 'Maya']])->id]);
	LicenceVariant::factory()->for($product, 'product')->create();

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
