<?php

declare(strict_types=1);

use App\Actions\Invoices\CancelInvoice;
use App\Actions\Invoices\RecordCardPayment;
use App\Actions\Licences\PlaceLicenceOrder;
use App\Enums\CancellationReason;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\User;
use App\Support\Accounting\AccountingSystem;
use App\Support\Payments\CardPayment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/**
 * Cancelling a licence order ([[CancelLicenceOrder]], Marcel 2026-10-20):
 * while nothing has been sent, paid or not; the invoice reversed in the books.
 */
beforeEach(function () {
	Storage::fake('documents');
	Mail::fake();
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$this->customer = User::factory()->create();
	$variant = LicenceVariant::factory()
		->for(LicenceProduct::factory()->create(['title' => ['de' => 'V-Ray']]), 'product')
		->create(['title' => ['de' => 'Solo'], 'sku' => 'VRY-1001', 'price' => '450.00']);
	$this->order = app(PlaceLicenceOrder::class)->execute($this->customer, [['variant' => $variant, 'quantity' => 1]]);
	$this->books = app(AccountingSystem::class);
});

function cancelOrder(): TestResponse
{
	return test()->actingAs(test()->admin)->patchJson('/api/admin/licence-orders/'.test()->order->uuid.'/cancel');
}

it('cancels an open order, its invoice, and reverses both in the books', function () {
	cancelOrder()->assertOk()
		->assertJsonPath('data.payment', 'cancelled')
		->assertJsonPath('data.cancelled_by', $this->admin->name)
		->assertJsonPath('data.refund', false);

	$invoice = $this->order->invoice->refresh();
	expect($invoice->status)->toBe(InvoiceStatus::Cancelled)
		->and($invoice->cancellation_reason)->toBe(CancellationReason::OrderCancelled)
		->and($this->books->reversals)->toBe([['number' => $invoice->number, 'paid' => false]]);

	$this->getJson('/api/admin/licence-orders?status=offen')->assertJsonCount(0, 'data');
	$this->getJson('/api/admin/licence-orders?status=storniert')->assertJsonCount(1, 'data');
});

it('cancels a paid order too, leaving the storno open for the refund', function () {
	$owed = (int) bcmul((string) $this->order->invoice->grand_total, '100', 0);
	app(RecordCardPayment::class)->execute(new CardPayment($this->order->invoice->uuid, $owed, 'chf', 'pi_1'));

	cancelOrder()->assertOk()->assertJsonPath('data.refund', true);

	expect($this->books->reversals[0]['paid'])->toBeTrue();
});

it('refuses once anything has been sent, and sends nothing once cancelled', function () {
	$item = $this->order->items->first();
	$this->actingAs($this->admin)->patchJson("/api/admin/licence-order-items/{$item->uuid}/dispatch", ['dispatched' => true])->assertOk();

	cancelOrder()->assertStatus(422)->assertJsonValidationErrors(['order' => 'bereits etwas versendet']);

	$this->patchJson("/api/admin/licence-order-items/{$item->uuid}/dispatch", ['dispatched' => false])->assertOk();
	cancelOrder()->assertOk();

	$this->patchJson("/api/admin/licence-order-items/{$item->uuid}/dispatch", ['dispatched' => true])->assertStatus(422);
});

it('cancels a free order, which has no invoice', function () {
	$demo = LicenceVariant::factory()->for(LicenceProduct::factory()->create(), 'product')->create(['price' => '0.00']);
	$this->order = app(PlaceLicenceOrder::class)->execute($this->customer, [['variant' => $demo, 'quantity' => 1]]);

	cancelOrder()->assertOk()->assertJsonPath('data.payment', 'cancelled');
	expect($this->books->reversals)->toBe([]);
});

it('keeps cancelling to admins', function () {
	$this->actingAs(User::factory()->expert()->create())
		->patchJson('/api/admin/licence-orders/'.$this->order->uuid.'/cancel')->assertForbidden();
});

it('shows the customer the order as cancelled, with nothing to pay', function () {
	cancelOrder();

	$this->actingAs($this->customer->forceFill(['email_verified_at' => now()]))
		->get('/de/konto')->assertOk()
		->assertSee('Storniert')
		->assertDontSee('Bezahlen</a>', false);
});

it('reverses every cancelled invoice in the books, a withdrawn course seat too', function () {
	$invoice = Invoice::factory()->create(['grand_total' => '499.00']);

	app(CancelInvoice::class)->execute($invoice, CancellationReason::BookingCancelled);

	expect($this->books->reversals)->toBe([['number' => $invoice->number, 'paid' => false]]);
});
