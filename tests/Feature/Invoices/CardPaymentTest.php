<?php

declare(strict_types=1);

use App\Actions\Invoices\RecordCardPayment;
use App\Actions\Licences\PlaceLicenceOrder;
use App\Enums\InvoiceStatus;
use App\Mail\InvoicePaidConfirmation;
use App\Mail\InvoicePaidNotification;
use App\Models\Invoice;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\Accounting\AccountingSystem;
use App\Support\Payments\CardPayment;
use App\Support\Payments\FakePaymentGateway;
use App\Support\Payments\PaymentGateway;
use App\Support\SiteUrl;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Paying an invoice by card ([[PaymentController]], #28), through the fake
 * Stripe ([[FakePaymentGateway]]): legacy's flow, with its two holes closed.
 */
beforeEach(function () {
	Storage::fake('documents');
	Mail::fake();
	config(['mail.admin' => 'office@viak.test']);

	$this->customer = User::factory()->create(['email_verified_at' => now()]);
	$variant = LicenceVariant::factory()
		->for(LicenceProduct::factory()->create(['title' => ['de' => 'V-Ray']]), 'product')
		->create(['title' => ['de' => 'Solo, named'], 'sku' => 'VRY-1001', 'price' => '450.00']);

	$this->order = app(PlaceLicenceOrder::class)->execute($this->customer, [['variant' => $variant, 'quantity' => 2]]);
	$this->invoice = $this->order->invoice;
});

/** *Weiter zur Zahlung*, then the fake's page: the session id Stripe would send back. */
function startCardPayment(Invoice $invoice): string
{
	$to = test()->post(SiteUrl::invoicePayment($invoice->uuid, 'de'))->assertRedirect()->headers->get('Location');

	expect($to)->toContain('/dev/zahlung/cs_fake_');

	return basename(parse_url($to, PHP_URL_PATH));
}

it('shows the invoice to its owner only, and asks for a login first', function () {
	$url = SiteUrl::invoicePayment($this->invoice->uuid, 'de');

	$this->get($url)->assertRedirect();

	$this->actingAs(User::factory()->create(['email_verified_at' => now()]))->get($url)->assertNotFound();
	$this->actingAs($this->customer)->post($url)->assertRedirect();

	$this->actingAs(User::factory()->create(['email_verified_at' => now()]))->post($url)->assertNotFound();

	$this->actingAs($this->customer)->get($url)->assertOk()
		->assertSee('Schritt 1/1')
		->assertSee('V-Ray')
		->assertSee('CHF '.$this->invoice->grand_total)
		->assertSee('inkl. MWST')
		->assertSee('Weiter zur Zahlung');
});

it('settles the invoice once Stripe says it is paid, with the order, the books and both mails', function () {
	$this->actingAs($this->customer);
	$session = startCardPayment($this->invoice);

	$back = $this->post(route('dev.payment.update', $session), ['action' => 'pay'])->assertRedirect()->headers->get('Location');
	expect($back)->toContain('/de/zahlung/erfolgreich?session_id='.$session);

	$this->get($back)->assertOk()->assertSee('Wir haben deine Zahlung für die Rechnung Nr. '.$this->invoice->number.' erhalten.');

	$invoice = $this->invoice->refresh();
	expect($invoice->status)->toBe(InvoiceStatus::Paid)
		->and($invoice->paid_at)->not->toBeNull()
		->and($invoice->card_payment)->toStartWith('pi_fake_')
		->and($this->order->refresh()->paid_at)->not->toBeNull()
		->and(app(AccountingSystem::class)->payments)->toBe([$invoice->number]);

	Mail::assertQueued(InvoicePaidConfirmation::class, fn ($mail) => $mail->hasTo($this->customer->email));
	Mail::assertQueued(InvoicePaidNotification::class, fn ($mail) => $mail->hasTo('office@viak.test'));

	$this->get(SiteUrl::invoicePayment($invoice->uuid, 'de'))->assertSee('Rechnung bezahlt')->assertDontSee('Weiter zur Zahlung');
	$this->post(SiteUrl::invoicePayment($invoice->uuid, 'de'))->assertRedirect(SiteUrl::invoicePayment($invoice->uuid, 'de'));
});

it('settles nothing on the return page alone: legacy marked an abandoned payment paid', function () {
	$this->actingAs($this->customer);
	$session = startCardPayment($this->invoice);

	$this->get(SiteUrl::paymentSuccess('de').'?session_id='.$session)->assertOk()->assertSee('Sobald deine Zahlung bestätigt ist');
	$this->get(SiteUrl::paymentSuccess('de'))->assertOk();

	expect($this->invoice->refresh()->status)->toBe(InvoiceStatus::Open);
	Mail::assertNotQueued(InvoicePaidConfirmation::class);
});

it('comes back from Abbrechen to the invoice, with legacy cancel sentence', function () {
	$this->actingAs($this->customer);
	$session = startCardPayment($this->invoice);

	$back = $this->post(route('dev.payment.update', $session), ['action' => 'cancel'])->headers->get('Location');

	$this->get($back)->assertOk()->assertSee('Du hast den Zahlungsvorgang abgebrochen.')->assertSee('Weiter zur Zahlung');
	expect($this->invoice->refresh()->status)->toBe(InvoiceStatus::Open);
});

it('settles from the webhook for a customer who never comes back, once however often it is called', function () {
	$this->actingAs($this->customer);
	$session = startCardPayment($this->invoice);
	app(PaymentGateway::class)->pay($session);
	auth()->logout();

	$payload = json_encode(['session' => $session]);
	$signed = fn () => $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => FakePaymentGateway::sign($payload), 'CONTENT_TYPE' => 'application/json'], $payload);

	$signed()->assertOk();
	$signed()->assertOk();

	expect($this->invoice->refresh()->status)->toBe(InvoiceStatus::Paid);
	Mail::assertQueuedCount(2);

	$this->actingAs($this->customer)->get(SiteUrl::paymentSuccess('de').'?session_id='.$session)->assertOk()->assertSee('Rechnung Nr. '.$this->invoice->number);
	Mail::assertQueuedCount(2);
});

it('refuses a webhook it did not sign', function () {
	$this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 'nope', 'CONTENT_TYPE' => 'application/json'], '{"session":"cs_fake_x"}')
		->assertStatus(400);
});

it('settles no payment that is not this invoice, and no invoice already settled', function () {
	$record = app(RecordCardPayment::class);
	$owed = (int) bcmul((string) $this->invoice->grand_total, '100', 0);

	expect($record->execute(new CardPayment($this->invoice->uuid, $owed - 1, 'chf', 'pi_short')))->toBeNull()
		->and($record->execute(new CardPayment($this->invoice->uuid, $owed, 'eur', 'pi_euro')))->toBeNull()
		->and($this->invoice->refresh()->status)->toBe(InvoiceStatus::Open);

	expect($record->execute(new CardPayment($this->invoice->uuid, $owed, 'chf', 'pi_first')))->not->toBeNull()
		->and($record->execute(new CardPayment($this->invoice->uuid, $owed, 'chf', 'pi_second')))->toBeNull()
		->and($this->invoice->refresh()->card_payment)->toBe('pi_first');

	$cancelled = Invoice::factory()->cancelled()->create(['user_id' => $this->customer->id, 'grand_total' => '10.00']);
	expect($record->execute(new CardPayment($cancelled->uuid, 1000, 'chf', 'pi_late')))->toBeNull();

	Mail::assertQueuedCount(2);
});

it('says so for a cancelled invoice instead of offering to pay it', function () {
	$cancelled = Invoice::factory()->cancelled()->create(['user_id' => $this->customer->id, 'grand_total' => '10.00']);

	$this->actingAs($this->customer)->get(SiteUrl::invoicePayment($cancelled->uuid, 'de'))
		->assertOk()->assertSee('storniert')->assertDontSee('Weiter zur Zahlung');
});

it('moves no real money from a prototype: a live key only in production, and no fake there', function () {
	config(['services.stripe.secret' => 'sk_live_123']);
	app()->forgetInstance(PaymentGateway::class);
	expect(fn () => app(PaymentGateway::class))->toThrow(RuntimeException::class, 'test key');

	config(['services.stripe.secret' => '']);
	app()->forgetInstance(PaymentGateway::class);
	app()->detectEnvironment(fn () => 'production');
	(new AppServiceProvider(app()))->register();
	expect(fn () => app(PaymentGateway::class))->toThrow(RuntimeException::class, 'not configured');
	app()->detectEnvironment(fn () => 'testing');
});

it('mails the payment as legacy did', function () {
	$this->invoice->forceFill(['status' => InvoiceStatus::Paid, 'paid_at' => now()])->save();

	(new InvoicePaidConfirmation($this->invoice))->assertHasSubject('Zahlungsbestätigung Rechnung '.$this->invoice->number)
		->assertSeeInHtml('Wir haben deine Zahlung erhalten')
		->assertSeeInHtml('Kreditkarte');
	(new InvoicePaidNotification($this->invoice))->assertSeeInHtml('Folgende Rechnung wurde per Kreditkarte bezahlt');
});
