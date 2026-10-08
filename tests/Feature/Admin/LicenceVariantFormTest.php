<?php

declare(strict_types=1);

use App\Enums\LicenceAccess;
use App\Enums\LicenceType;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\User;

/**
 * A licence product's variants — `/api/admin/licences/{product}/variants` and
 * `/api/admin/licence-variants/{variant}` ([[05-licences]]): a list on the
 * product's form, each saved in a form of its own.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$this->product = LicenceProduct::factory()->create();
});

function variantPayload(array $overrides = []): array
{
	return [
		'title' => 'Solo, named',
		'sku' => 'VRY-1001',
		'price' => '450',
		'licence_type' => 'subscription',
		'access' => 'named',
		'platforms' => ['windows', 'macos'],
		'min_quantity' => '',
		'note' => '',
		'listed' => true,
		...$overrides,
	];
}

it('keeps variants to admins', function () {
	$this->actingAs(User::factory()->expert()->create())
		->postJson("/api/admin/licences/{$this->product->uuid}/variants", variantPayload())->assertForbidden();
});

it('adds a variant at the end of the dropdown, the price net to the centime', function () {
	LicenceVariant::factory()->for($this->product, 'product')->create(['order' => 3]);

	$this->actingAs($this->admin)->postJson("/api/admin/licences/{$this->product->uuid}/variants", variantPayload())
		->assertCreated()
		->assertJsonPath('data.price', '450.00')
		->assertJsonPath('data.product.uuid', $this->product->uuid);

	expect(LicenceVariant::where('sku', 'VRY-1001')->first()->order)->toBe(4);
});

it('takes a demo: free and without a licence type, shown as the empty choice', function () {
	$uuid = $this->actingAs($this->admin)
		->postJson("/api/admin/licences/{$this->product->uuid}/variants", variantPayload(['title' => 'Demoversion', 'price' => '0', 'licence_type' => '']))
		->assertCreated()
		->assertJsonPath('data.licence_type', '')
		->assertJsonPath('data.price', '0.00')
		->json('data.uuid');

	expect(LicenceVariant::where('uuid', $uuid)->first()->licence_type)->toBeNull();
});

it('refuses an article number another variant has, but not its own', function () {
	LicenceVariant::factory()->create(['sku' => 'VRY-1001']);

	$this->actingAs($this->admin)->postJson("/api/admin/licences/{$this->product->uuid}/variants", variantPayload())
		->assertJsonPath('errors.sku.0', 'Diese Artikelnummer hat bereits eine andere Variante.');

	$own = LicenceVariant::factory()->for($this->product, 'product')->create(['sku' => 'VRY-2001']);
	$this->putJson("/api/admin/licence-variants/{$own->uuid}", variantPayload(['sku' => 'VRY-2001']))->assertOk();
});

it('asks for a title, an article number and a price', function () {
	$this->actingAs($this->admin)
		->postJson("/api/admin/licences/{$this->product->uuid}/variants", variantPayload(['title' => '', 'sku' => '', 'price' => '']))
		->assertJsonValidationErrors(['title', 'sku', 'price']);
});

it('saves the order the dropdown is dragged into, and only this product\'s variants', function () {
	[$a, $b] = LicenceVariant::factory()->for($this->product, 'product')->count(2)->create();
	$other = LicenceVariant::factory()->create();

	$this->actingAs($this->admin)
		->postJson("/api/admin/licences/{$this->product->uuid}/variants/order", ['variants' => [$b->uuid, $a->uuid]])
		->assertNoContent();

	expect($this->product->variants()->pluck('uuid')->all())->toBe([$b->uuid, $a->uuid]);

	$this->postJson("/api/admin/licences/{$this->product->uuid}/variants/order", ['variants' => [$other->uuid]])
		->assertJsonValidationErrors('variants.0');
});

it('sends back exactly what it loads', function () {
	$variant = LicenceVariant::factory()->for($this->product, 'product')->create(['min_quantity' => 3, 'note' => ['de' => 'Mindestens drei']]);

	$form = $this->actingAs($this->admin)->getJson("/api/admin/licence-variants/{$variant->uuid}")->json('data');

	expect($this->putJson("/api/admin/licence-variants/{$variant->uuid}", $form)->assertOk()->json('data'))->toBe($form);
});

it('lists a variant with its type and use, which its name alone often leaves out', function () {
	LicenceVariant::factory()->for($this->product, 'product')->create(['title' => ['de' => 'floating'], 'licence_type' => LicenceType::Perpetual, 'access' => LicenceAccess::Floating]);
	LicenceVariant::factory()->for($this->product, 'product')->demo()->create(['access' => LicenceAccess::Named]);

	$labels = $this->actingAs($this->admin)->getJson("/api/admin/licences/{$this->product->uuid}/variants")->json('data.*.labels');

	expect($labels)->toBe([['Dauerlizenz', 'Netzwerk (floating)'], ['Demo', 'Einzelplatz (named)']]);
});

it('deletes a variant softly', function () {
	$variant = LicenceVariant::factory()->for($this->product, 'product')->create();

	$this->actingAs($this->admin)->deleteJson("/api/admin/licence-variants/{$variant->uuid}")->assertNoContent();

	expect(LicenceVariant::withTrashed()->find($variant->id)->trashed())->toBeTrue();
});
