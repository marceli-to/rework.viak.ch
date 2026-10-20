<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Mail\InvoicePaidConfirmation;
use App\Mail\LicenceOrderConfirmation;
use App\Mail\LicenceOrderInfoAdmin;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Course;
use App\Models\Event;
use App\Models\LicenceOrder;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;
use App\Models\User;
use App\Support\SiteUrl;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Licences through the checkout ([[13-checkout]]): an open basket, the order
 * placed and invoiced at *Bestellen*, the card taken on Stripe's page (the
 * fake, [[FakePaymentGateway]]) and the courses beside them booked as before.
 */
beforeEach(function () {
	Storage::fake('documents');
	Mail::fake();
	config(['mail.admin' => 'office@viak.test']);
	Country::query()->firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);

	$this->buyer = User::factory()->create(['email_verified_at' => now(), 'email' => 'kim@example.test']);
	$this->vray = LicenceVariant::factory()
		->for(LicenceProduct::factory()->create(['title' => ['de' => 'V-Ray'], 'publish' => true]), 'product')
		->create(['title' => ['de' => 'Solo'], 'sku' => 'VRY-1001', 'price' => '450.55', 'listed' => true, 'min_quantity' => 1]);
	$this->vray->product->hosts()->attach(Software::create(['title' => ['de' => 'SketchUp Pro']]));
	$this->demo = LicenceVariant::factory()
		->for(LicenceProduct::factory()->create(['title' => ['de' => 'Enscape'], 'publish' => true]), 'product')
		->create(['title' => ['de' => 'Demo'], 'sku' => 'ENS-0', 'price' => '0.00', 'listed' => true, 'min_quantity' => 1]);
});

function vrayLine(int $quantity = 2): array
{
	return ['variant' => test()->vray->uuid, 'quantity' => $quantity, 'host' => 'SketchUp Pro'];
}

it('prices licences for a guest, with VAT per line as the invoice will have it', function () {
	$this->postJson('/api/basket/price', ['licences' => [vrayLine(3)]])
		->assertOk()
		->assertJsonPath('data.licences.0.product', 'V-Ray')
		->assertJsonPath('data.licences.0.host', 'SketchUp Pro')
		->assertJsonPath('data.licences.0.net', '1351.65')
		->assertJsonPath('data.licence_vat', '109.48')
		->assertJsonPath('data.licence_total', '1461.13')
		->assertJsonPath('data.booking_total', '0.00')
		->assertJsonPath('data.total', '1461.13');
});

it('refuses a licence without its host, under its minimum, or no longer in the shop', function () {
	$this->postJson('/api/basket/price', ['licences' => [['variant' => $this->vray->uuid, 'quantity' => 1]]])
		->assertStatus(422)->assertJsonValidationErrors(['licences.0.host' => 'bitte Hostsoftware wählen']);

	$this->vray->update(['min_quantity' => 3]);
	$this->postJson('/api/basket/price', ['licences' => [vrayLine(2)]])
		->assertStatus(422)->assertJsonValidationErrors(['licences.0.quantity' => 'mindestens 3 Stück']);

	$this->vray->update(['listed' => false]);
	$this->postJson('/api/basket/price', ['licences' => [vrayLine(3)]])
		->assertStatus(422)->assertJsonValidationErrors(['licences.0.variant' => 'nicht mehr bestellbar']);
});

it('keeps the licence email on the account, and nothing when it is the account own', function () {
	$this->actingAs($this->buyer)->post('/de/checkout/address', ['licence_email' => 'it@firma.test'])
		->assertRedirect('/de/checkout/payment');
	expect($this->buyer->refresh()->licenceEmail())->toBe('it@firma.test');

	$this->post('/de/checkout/address', ['licence_email' => 'KIM@example.test']);
	expect($this->buyer->refresh()->licence_email)->toBeNull();

	$this->post('/de/checkout/address', []);
	expect($this->buyer->refresh()->licence_email)->toBeNull();
});

it('orders licences, invoices them, and sends the customer to pay by card', function () {
	$this->buyer->update(['licence_email' => 'it@firma.test']);

	$to = $this->actingAs($this->buyer)
		->post('/de/checkout/summary', ['licences' => [vrayLine(2)], 'total_shown' => '974.09'])
		->assertRedirect()->headers->get('Location');

	expect($to)->toContain('/dev/zahlung/cs_fake_');

	$order = LicenceOrder::query()->sole();
	expect($order->delivery_email)->toBe('it@firma.test')
		->and($order->items->sole()->host)->toBe('SketchUp Pro')
		->and($order->invoice->grand_total)->toBe('974.09')
		->and($order->isPaid())->toBeFalse();

	Mail::assertQueued(LicenceOrderConfirmation::class, fn ($mail) => $mail->hasTo('kim@example.test'));
	Mail::assertQueued(LicenceOrderInfoAdmin::class, fn ($mail) => $mail->hasTo('office@viak.test'));

	$session = basename(parse_url($to, PHP_URL_PATH));
	$back = $this->post(route('dev.payment.update', $session), ['action' => 'pay'])->headers->get('Location');
	expect($back)->toContain('/de/checkout/confirmation?session_id=');

	$this->get($back)->assertOk()
		->assertSee('Bestellung abgeschlossen')
		->assertSee('Deine Zahlung ist')
		->assertSee('it@firma.test');

	expect($order->refresh()->isPaid())->toBeTrue()
		->and($order->invoice->status)->toBe(InvoiceStatus::Paid);
	Mail::assertQueued(InvoicePaidConfirmation::class);
});

it('books the courses and orders the licences from one basket, the courses billed later', function () {
	$event = Event::factory()->for(Course::factory()->create(['fee' => '499.00']))->create(['max_participants' => 10]);

	$this->actingAs($this->buyer)
		->post('/de/checkout/summary', [
			'items' => [['event' => $event->uuid, 'rental' => 0]],
			'licences' => [vrayLine(1)],
			'total_shown' => '986.04',
		])
		->assertSessionHasNoErrors()
		->assertRedirect();

	expect(Booking::query()->where('user_id', $this->buyer->id)->count())->toBe(1)
		->and(LicenceOrder::query()->sole()->invoice->grand_total)->toBe('487.04')
		->and(Booking::query()->sole()->checkout->net)->toBe('499.00');
});

it('refuses a total the customer has not seen, licences included', function () {
	$this->actingAs($this->buyer)
		->from('/de/checkout/summary')
		->post('/de/checkout/summary', ['licences' => [vrayLine(1)], 'total_shown' => '450.55'])
		->assertRedirect('/de/checkout/summary')
		->assertSessionHasErrors();

	expect(LicenceOrder::query()->count())->toBe(0);
});

it('places a free order without Stripe and without an invoice', function () {
	$this->actingAs($this->buyer)
		->post('/de/checkout/summary', ['licences' => [['variant' => $this->demo->uuid, 'quantity' => 1]], 'total_shown' => '0.00'])
		->assertRedirect('/de/checkout/confirmation');

	expect(LicenceOrder::query()->sole()->invoice)->toBeNull();

	$this->get('/de/checkout/confirmation')->assertOk()->assertSee('Wir senden die')->assertDontSee('Jetzt bezahlen');
});

it('leaves an abandoned payment waiting, payable from the confirmation and the profile', function () {
	$to = $this->actingAs($this->buyer)
		->post('/de/checkout/summary', ['licences' => [vrayLine(1)], 'total_shown' => '487.04'])
		->headers->get('Location');

	$back = $this->post(route('dev.payment.update', basename(parse_url($to, PHP_URL_PATH))), ['action' => 'cancel'])->headers->get('Location');
	$invoice = LicenceOrder::query()->sole()->invoice;

	$this->get($back)->assertOk()
		->assertSee('noch nicht bezahlt')
		->assertSee(SiteUrl::invoicePayment($invoice->uuid, 'de'));

	$this->get('/de/konto')->assertOk()
		->assertSee('Bestellungen')
		->assertSee('Rechnung offen')
		->assertSee('Bezahlen');
});

it('shows no Bestellungen to a customer who has none', function () {
	$this->actingAs($this->buyer)->get('/de/konto')->assertOk()->assertDontSee('Bestellungen');
});
