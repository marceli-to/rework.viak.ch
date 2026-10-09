<?php

declare(strict_types=1);

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\LicenceOrder;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Storage;

/**
 * Licence orders on the dashboard ([[05-licences]]): an order VIAK enters by
 * hand, with a free line (#36), and the dispatch worklist.
 */
beforeEach(function () {
	Storage::fake('documents');
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$this->customer = User::factory()->create();
	$this->product = LicenceProduct::factory()->create(['title' => ['de' => 'V-Ray']]);
	$this->variant = LicenceVariant::factory()->for($this->product, 'product')
		->create(['title' => ['de' => 'Solo, named'], 'sku' => 'VRY-1001', 'price' => '450.00']);
});

function placeOrder(User $customer, array $payload)
{
	return test()->postJson("/api/admin/customers/{$customer->uuid}/licence-orders", $payload);
}

it('keeps licence orders to admins', function () {
	$this->actingAs(User::factory()->expert()->create())
		->getJson('/api/admin/licence-orders?status=offen')->assertForbidden();
	$this->postJson("/api/admin/customers/{$this->customer->uuid}/licence-orders", ['lines' => []])->assertForbidden();
});

it('enters an order with a variant and a free line, and invoices it at once with VAT', function () {
	$uuid = $this->actingAs($this->admin)->post("/api/admin/customers/{$this->customer->uuid}/licence-orders", [
		'lines' => [
			['variant' => $this->variant->uuid, 'quantity' => 2],
			['free' => true, 'title' => 'V-Ray Solo, 4 Monate', 'price' => '180', 'quantity' => 1],
		],
	], ['Accept' => 'application/json'])->assertCreated()->json('data.uuid');

	$order = LicenceOrder::where('uuid', $uuid)->with('items', 'invoice.items')->first();

	expect($order->entered_by)->toBe($this->admin->id)
		->and($order->items)->toHaveCount(2)
		->and($order->items[0]->title)->toBe('V-Ray, Solo, Jahresmietlizenz, Einzelplatz (named)')
		->and($order->items[0]->sku)->toBe('VRY-1001')
		->and($order->items[1]->licence_variant_id)->toBeNull()
		->and($order->items[1]->price)->toBe('180.00')
		->and($order->paid_at)->toBeNull();

	$invoice = $order->invoice;
	expect($invoice->user_id)->toBe($this->customer->id)
		->and($invoice->net)->toBe('1080.00')
		->and($invoice->vat)->toBe('87.48')
		->and($invoice->grand_total)->toBe('1167.48')
		->and($invoice->status)->toBe(InvoiceStatus::Open)
		->and($invoice->items->pluck('type')->all())->toBe([InvoiceItemType::Licence, InvoiceItemType::Licence])
		->and($invoice->items[0]->description)->toBe('2 × V-Ray, Solo, Jahresmietlizenz, Einzelplatz (named)')
		->and($invoice->items[0]->itemable->is($order->items[0]))->toBeTrue()
		->and($invoice->document)->not->toBeNull();
});

it('freezes the line: a later price change does not reach the order', function () {
	$this->actingAs($this->admin);
	$uuid = placeOrder($this->customer, ['lines' => [['variant' => $this->variant->uuid, 'quantity' => 1]]])->json('data.uuid');

	$this->variant->update(['price' => '999.00', 'title' => ['de' => 'Anders']]);

	$item = LicenceOrder::where('uuid', $uuid)->first()->items->first();
	expect($item->price)->toBe('450.00')->and($item->title)->toBe('V-Ray, Solo, Jahresmietlizenz, Einzelplatz (named)');
});

it('takes a hidden variant, which only VIAK enters', function () {
	$hidden = LicenceVariant::factory()->hidden()->for($this->product, 'product')->create();

	$this->actingAs($this->admin);
	placeOrder($this->customer, ['lines' => [['variant' => $hidden->uuid, 'quantity' => 1]]])->assertCreated();
});

it('raises no invoice for a free order, which is paid and still on the worklist', function () {
	$demo = LicenceVariant::factory()->demo()->for($this->product, 'product')->create();

	$this->actingAs($this->admin);
	$uuid = placeOrder($this->customer, ['lines' => [['variant' => $demo->uuid, 'quantity' => 1]]])->json('data.uuid');

	$order = LicenceOrder::where('uuid', $uuid)->first();
	expect($order->invoice_id)->toBeNull()->and($order->paid_at)->not->toBeNull()->and(Invoice::count())->toBe(0);

	$this->getJson('/api/admin/licence-orders?status=offen')
		->assertJsonPath('data.0.uuid', $uuid)
		->assertJsonPath('data.0.payment', 'free');
});

it('freezes the invoice address picked from the customer\'s own', function () {
	$address = UserAddress::factory()->for($this->customer)->create(['company' => 'Muster AG', 'city' => 'Bern']);
	$foreign = UserAddress::factory()->create();

	$this->actingAs($this->admin);
	placeOrder($this->customer, ['invoice_address' => $foreign->uuid, 'lines' => [['variant' => $this->variant->uuid, 'quantity' => 1]]])
		->assertJsonValidationErrors('invoice_address');

	$uuid = placeOrder($this->customer, ['invoice_address' => $address->uuid, 'lines' => [['variant' => $this->variant->uuid, 'quantity' => 1]]])
		->assertCreated()->json('data.uuid');

	$order = LicenceOrder::where('uuid', $uuid)->first();
	expect($order->invoice_address['company'])->toBe('Muster AG')
		->and($order->invoice->invoice_address['city'])->toBe('Bern');
});

it('asks for a line, and for a free line\'s title and price', function () {
	$this->actingAs($this->admin);
	placeOrder($this->customer, ['lines' => []])->assertJsonValidationErrors('lines');
	placeOrder($this->customer, ['lines' => [['free' => true, 'title' => '', 'price' => '', 'quantity' => 1]]])
		->assertJsonValidationErrors(['lines.0.title', 'lines.0.price'])
		->assertJsonMissingValidationErrors('lines.0.variant');
	placeOrder($this->customer, ['lines' => [['variant' => '', 'quantity' => 1]]])
		->assertJsonPath('errors', ['lines.0.variant' => ['Bitte die Software wählen.']]);
	placeOrder($this->customer, ['lines' => [['variant' => $this->variant->uuid, 'quantity' => 0]]])
		->assertJsonValidationErrors('lines.0.quantity');
});

it('asks a plugin for its host software and freezes the choice', function () {
	$plugin = LicenceProduct::factory()->create(['title' => ['de' => 'Maxwell V5']]);
	$plugin->hosts()->attach([Software::create(['title' => ['de' => 'Rhinoceros']])->id, Software::create(['title' => ['de' => 'SketchUp']])->id]);
	$variant = LicenceVariant::factory()->for($plugin, 'product')->create();

	$this->actingAs($this->admin);
	placeOrder($this->customer, ['lines' => [['variant' => $variant->uuid, 'quantity' => 1]]])
		->assertJsonValidationErrors('lines.0.host');

	$uuid = placeOrder($this->customer, ['lines' => [['variant' => $variant->uuid, 'quantity' => 1, 'host' => 'SketchUp']]])->json('data.uuid');
	$order = LicenceOrder::where('uuid', $uuid)->first();

	expect($order->items->first()->host)->toBe('SketchUp')
		->and($order->invoice->items->first()->description)->toEndWith(', für SketchUp');
});

it('refuses an order for a deactivated account', function () {
	$this->customer->forceFill(['deactivated_at' => now()])->save();

	$this->actingAs($this->admin);
	placeOrder($this->customer, ['lines' => [['variant' => $this->variant->uuid, 'quantity' => 1]]])->assertStatus(422);
});

it('keeps an order on the worklist until every line is sent, and records who sent each', function () {
	$this->actingAs($this->admin);
	$uuid = placeOrder($this->customer, ['lines' => [
		['variant' => $this->variant->uuid, 'quantity' => 1],
		['free' => true, 'title' => 'Freie Position', 'price' => '10', 'quantity' => 1],
	]])->json('data.uuid');
	$items = LicenceOrder::where('uuid', $uuid)->first()->items;

	$this->patchJson("/api/admin/licence-order-items/{$items[0]->uuid}/dispatch", ['dispatched' => true])
		->assertOk()
		->assertJsonPath('data.items.0.dispatched_by', $this->admin->name);

	$this->getJson('/api/admin/licence-orders?status=offen')->assertJsonPath('data.0.open', 1);
	$this->getJson('/api/admin/licence-orders?status=versendet')->assertJsonCount(0, 'data');

	$this->patchJson("/api/admin/licence-order-items/{$items[1]->uuid}/dispatch", ['dispatched' => true])->assertOk();

	$this->getJson('/api/admin/licence-orders?status=offen')->assertJsonCount(0, 'data');
	$this->getJson('/api/admin/licence-orders?status=versendet')->assertJsonPath('data.0.uuid', $uuid);

	$this->patchJson("/api/admin/licence-order-items/{$items[1]->uuid}/dispatch", ['dispatched' => false])
		->assertJsonPath('data.items.1.dispatched_at', null);
	$this->getJson('/api/admin/licence-orders?status=offen')->assertJsonPath('data.0.uuid', $uuid);
});

it('shows the order paid once its invoice is', function () {
	$this->actingAs($this->admin);
	$uuid = placeOrder($this->customer, ['lines' => [['variant' => $this->variant->uuid, 'quantity' => 1]]])->json('data.uuid');

	$this->getJson("/api/admin/licence-orders/{$uuid}")->assertJsonPath('data.payment', 'open');

	LicenceOrder::where('uuid', $uuid)->first()->invoice->forceFill(['status' => InvoiceStatus::Paid])->save();

	$this->getJson("/api/admin/licence-orders/{$uuid}")->assertJsonPath('data.payment', 'paid');
});

it('searches by number, customer and what was ordered', function () {
	$other = User::factory()->create(['last_name' => 'Zwicky']);

	$this->actingAs($this->admin);
	placeOrder($this->customer, ['lines' => [['variant' => $this->variant->uuid, 'quantity' => 1]]]);
	placeOrder($other, ['lines' => [['free' => true, 'title' => 'Enscape, 6 Monate', 'price' => '200', 'quantity' => 1]]]);

	$this->getJson('/api/admin/licence-orders?status=offen&suche=zwicky')->assertJsonCount(1, 'data');
	$this->getJson('/api/admin/licence-orders?status=offen&suche=VRY-1001')->assertJsonCount(1, 'data');
	$this->getJson('/api/admin/licence-orders?status=offen&suche=enscape')->assertJsonCount(1, 'data');
});

it('hands the entry form the catalogue, hidden variants marked, and the customer\'s addresses', function () {
	LicenceVariant::factory()->hidden()->for($this->product, 'product')->create(['title' => ['de' => 'EDU']]);
	UserAddress::factory()->for($this->customer)->create();

	$this->actingAs($this->admin)->getJson("/api/admin/customers/{$this->customer->uuid}/licence-orders/create")
		->assertOk()
		->assertJsonPath('data.customer.uuid', $this->customer->uuid)
		->assertJsonCount(1, 'data.addresses')
		->assertJsonPath('data.products.0.title', 'V-Ray')
		->assertJsonCount(2, 'data.products.0.variants')
		->assertJsonPath('data.products.0.variants.1.listed', false);
});
