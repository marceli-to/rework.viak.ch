<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Checkout;
use App\Models\Country;
use App\Models\Course;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\User;
use App\Models\UserAddress;
use App\Support\CheckoutSession;

/**
 * Steps 3 and 4, and the thing at the end of them ([[09-public-site]]).
 *
 * The summary is the only irreversible POST on the public site, and the only
 * one where the browser hands the server a selection the server does not hold.
 * So what is asserted here is mostly what the server refuses to take on trust.
 */
function purchaser(): User
{
	return User::factory()->student()->create([
		'email_verified_at' => now(),
		'first_name' => 'Antonia',
		'last_name' => 'Haller',
		'street' => 'Kaiserstr.',
		'street_no' => '76',
		'zip' => '7752',
		'city' => 'Orsières',
	]);
}

function sellableEvent(string $fee = '499.00'): Event
{
	return Event::factory()
		->for(Course::factory()->create(['fee' => $fee]))
		->create(['max_participants' => 10, 'rentals_available' => true]);
}

beforeEach(function () {
	Country::query()->firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
});

it('takes no payment on the payment step', function () {
	$this->actingAs(purchaser())
		->get('/de/checkout/payment')
		->assertOk()
		->assertSee('Schritt 3/4')
		->assertSee('Zahlungsoptionen')
		->assertSee('QR-Einzahlungsschein')
		->assertSee('Gutschein-Code')
		// There is no Stripe anywhere in this flow: invoices are raised on
		// `EventConfirmed`, days or weeks later ([[03-invoices]]).
		->assertDontSee('stripe', false);
});

it('shows both addresses on the summary, and only the invoice one when there is one', function () {
	$user = purchaser();

	$this->actingAs($user)
		->get('/de/checkout/summary')
		->assertOk()
		->assertSee('Schritt 4/4')
		->assertSee('Antonia Haller')
		->assertDontSee('Rechnungsadresse');

	$address = UserAddress::factory()->for($user)->create([
		'company' => 'RAV Zürich',
		'city' => 'Ilanz',
		'country_code' => 'ch',
	]);
	CheckoutSession::setInvoiceAddress($address);

	$this->actingAs($user)
		->get('/de/checkout/summary')
		->assertOk()
		->assertSee('Rechnungsadresse')
		->assertSee('RAV Zürich');
});

it('turns a basket into bookings and lands on the confirmation', function () {
	$user = purchaser();
	$event = sellableEvent('499.00');

	$this->actingAs($user)
		->post('/de/checkout/summary', [
			'items' => [['event' => $event->uuid]],
			'total_shown' => '499.00',
		])
		->assertRedirect('/de/checkout/confirmation');

	$booking = Booking::query()->sole();

	expect($booking->user_id)->toBe($user->id)
		->and($booking->event_id)->toBe($event->id)
		->and($booking->course_fee)->toBe('499.00')
		->and(Checkout::query()->count())->toBe(1);
});

/**
 * The laptop's VAT is the reason the summary has a row legacy does not.
 * `getTotals()` hard-zeroes VAT under a `@todo`, so legacy quotes a total the
 * customer is not charged — and our own `total_shown` guard is checked against
 * the figure they actually pay, so quoting net would be refused by us.
 */
it('charges VAT on the laptop and nothing else', function () {
	$user = purchaser();
	$event = sellableEvent('499.00');

	$this->actingAs($user)
		->post('/de/checkout/summary', [
			'items' => [['event' => $event->uuid, 'rental' => 1]],
			// 499 course + 80 laptop + 8.1 % of 80
			'total_shown' => '585.48',
		])
		->assertRedirect('/de/checkout/confirmation');

	expect(Checkout::query()->sole()->net)->toBe('579.00');
});

/**
 * The whole point of `total_shown`. Legacy had no such check, so a basket left
 * open while a fee was edited charged whatever the number happened to be at
 * `Booking::create()`.
 */
it('refuses a checkout whose total has moved, without showing the customer JSON', function () {
	$user = purchaser();
	$event = sellableEvent('499.00');

	$this->actingAs($user)
		->from('/de/checkout/summary')
		->post('/de/checkout/summary', [
			'items' => [['event' => $event->uuid]],
			'total_shown' => '99.00',
		])
		->assertRedirect('/de/checkout/summary')
		->assertSessionHasErrors('total_shown');

	expect(Booking::query()->count())->toBe(0);
});

it('refuses the last seat twice, and says so in German rather than in braces', function () {
	$user = purchaser();
	$event = Event::factory()
		->for(Course::factory()->create(['fee' => '499.00']))
		->create(['max_participants' => 1]);

	Booking::factory()->for(User::factory())->for($event)->create();

	$this->actingAs($user)
		->from('/de/checkout/summary')
		->post('/de/checkout/summary', [
			'items' => [['event' => $event->uuid]],
			'total_shown' => '499.00',
		])
		->assertRedirect('/de/checkout/summary')
		->assertSessionHasErrors('items');

	expect(Booking::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('refuses a code that will not apply', function () {
	$user = purchaser();
	$event = sellableEvent('499.00');
	$expired = DiscountCode::factory()->create(['valid_to' => now()->subDay()]);

	$this->actingAs($user)
		->from('/de/checkout/summary')
		->post('/de/checkout/summary', [
			'items' => [['event' => $event->uuid]],
			'code' => $expired->code,
			'total_shown' => '449.00',
		])
		->assertRedirect('/de/checkout/summary')
		->assertSessionHasErrors('code');

	expect(Booking::query()->count())->toBe(0);
});

/**
 * The shape question three places disagreed about, settled 2026-09-22: what is
 * frozen is what a `UserAddress` has, taken **at the moment of purchase** rather
 * than when the customer picked it. The session holds a uuid precisely so this
 * can be true.
 */
it('freezes the invoice address as it is now, not as it was at step 2', function () {
	$user = purchaser();
	$event = sellableEvent('499.00');

	$address = UserAddress::factory()->for($user)->create([
		'company' => 'RAV Zürich',
		'street' => 'Betschartplatz',
		'street_no' => '118',
		'zip' => '7204',
		'city' => 'Ilanz',
		'country_code' => 'ch',
	]);
	CheckoutSession::setInvoiceAddress($address);

	$address->update(['city' => 'Chur', 'zip' => '7000']);

	$this->actingAs($user)
		->post('/de/checkout/summary', [
			'items' => [['event' => $event->uuid]],
			'total_shown' => '499.00',
		])
		->assertRedirect('/de/checkout/confirmation');

	$frozen = Booking::query()->sole()->invoice_address;

	expect($frozen['city'])->toBe('Chur')
		->and($frozen['company'])->toBe('RAV Zürich')
		->and($frozen['street_no'])->toBe('118')
		->and($frozen['country_code'])->toBe('ch')
		->and($frozen)->not->toHaveKey('lines');
});

it('clears the checkout’s session state once it has completed', function () {
	$user = purchaser();
	$event = sellableEvent('499.00');
	CheckoutSession::setInvoiceAddress(UserAddress::factory()->for($user)->create());

	$this->actingAs($user)
		->post('/de/checkout/summary', [
			'items' => [['event' => $event->uuid]],
			'total_shown' => '499.00',
		])
		->assertRedirect('/de/checkout/confirmation');

	expect(CheckoutSession::invoiceAddress($user))->toBeNull();
});

/**
 * The basket is in `localStorage`, so the server cannot empty it on the way
 * past. The confirmation page is what tells the browser to.
 */
it('tells the browser to empty its basket on the confirmation', function () {
	$this->actingAs(purchaser())
		->get('/de/checkout/confirmation')
		->assertOk()
		->assertSee('Buchung abgeschlossen')
		->assertSee('Vielen Dank für Deine Buchung')
		->assertSee('$store.basket.clear()', false);
});

it('keeps every checkout step behind the same guards', function (string $url) {
	$this->get($url)->assertRedirect('/login');

	$admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$this->actingAs($admin)->get($url)->assertForbidden();
})->with([
	'/de/checkout/payment',
	'/de/checkout/summary',
	'/de/checkout/confirmation',
]);
