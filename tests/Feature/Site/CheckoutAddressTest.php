<?php

declare(strict_types=1);

use App\Models\Country;
use App\Models\User;
use App\Models\UserAddress;
use App\Support\CheckoutSession;

/**
 * Step 2 of 4 — *Kontakt* ([[09-public-site]]).
 *
 * The first checkout step with server state: the basket is in the browser, but
 * where the bill goes is an answer, and answers live in the session
 * ([[CheckoutSession]]). It is also the first step that is a plain Blade form
 * rather than a fetch, which is the shape `00-foundation.md` settled on.
 */
function buyer(array $attributes = []): User
{
	return User::factory()->student()->create([
		'email_verified_at' => now(),
		'first_name' => 'Antonia',
		'last_name' => 'Haller',
		'street' => 'Kaiserstr.',
		'street_no' => '76',
		'zip' => '7752',
		'city' => 'Orsières',
		...$attributes,
	]);
}

beforeEach(function () {
	Country::query()->firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
	Country::query()->firstOrCreate(['code' => 'de'], ['name' => ['de' => 'Deutschland'], 'order' => 2]);
});

it('is behind the same guards as the rest of the checkout', function () {
	$this->get('/de/checkout/address')->assertRedirect('/login');

	$admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$this->actingAs($admin)->get('/de/checkout/address')->assertForbidden();
});

it('prints the customer’s own address without asking the browser for it', function () {
	$this->actingAs(buyer(['company' => 'Nookla GmbH']))
		->get('/de/checkout/address')
		->assertOk()
		->assertSee('Schritt 2/4')
		->assertSee('Kursteilnehmer')
		->assertSee('Nookla GmbH')
		->assertSee('Antonia Haller')
		->assertSee('Kaiserstr. 76')
		->assertSee('7752 Orsières');
});

/**
 * Legacy's `address_str` — company, name, city, and **no street**, so two
 * addresses at the same firm in the same town read identically. Carried across
 * as found.
 */
it('lists saved invoice addresses the way legacy labels them', function () {
	$user = buyer();
	UserAddress::factory()->for($user)->create([
		'company' => 'RAV Zürich',
		'first_name' => 'Jean',
		'last_name' => 'Hofmann',
		'street' => 'Betschartplatz',
		'street_no' => '118',
		'city' => 'Ilanz',
		'country_code' => 'ch',
	]);

	$this->actingAs($user)
		->get('/de/checkout/address')
		->assertOk()
		->assertSee('RAV Zürich, Jean Hofmann, Ilanz')
		->assertDontSee('Betschartplatz');
});

it('names the country only when it is not Switzerland', function () {
	$user = buyer();
	$swiss = UserAddress::factory()->for($user)->create(['country_code' => 'ch', 'city' => 'Bern']);
	$german = UserAddress::factory()->for($user)->create(['country_code' => 'de', 'city' => 'Konstanz']);

	expect($swiss->summary())->not->toContain('Schweiz')
		->and($german->summary())->toContain('Deutschland')
		->and($german->lines())->toContain('Deutschland')
		->and($swiss->lines())->not->toContain('Schweiz');
});

/**
 * The common case, and a real answer rather than a missing one: 126 of 710
 * bookings use a separate address, so *entspricht Teilnehmer-Adresse* is what
 * most customers post.
 */
it('takes an empty answer as “same as the participant”', function () {
	$user = buyer();
	$address = UserAddress::factory()->for($user)->create();
	CheckoutSession::setInvoiceAddress($address);

	$this->actingAs($user)
		->post('/de/checkout/address', [])
		->assertRedirect('/de/checkout/payment');

	expect(CheckoutSession::invoiceAddress($user))->toBeNull();
});

it('remembers the chosen address across the step', function () {
	$user = buyer();
	$address = UserAddress::factory()->for($user)->create();

	$this->actingAs($user)
		->post('/de/checkout/address', ['invoice_address' => $address->uuid])
		->assertRedirect('/de/checkout/payment');

	expect(CheckoutSession::invoiceAddress($user)?->id)->toBe($address->id);

	$this->actingAs($user)->get('/de/checkout/address')->assertOk()->assertSee($address->uuid);
});

/**
 * `exists:user_addresses,uuid` would have been enough to pass validation and
 * wrong: a guessed uuid would bill a stranger's employer. The row has to be the
 * customer's own.
 */
it('refuses an address belonging to somebody else', function () {
	$user = buyer();
	$theirs = UserAddress::factory()->for(User::factory())->create();

	$this->actingAs($user)
		->post('/de/checkout/address', ['invoice_address' => $theirs->uuid])
		->assertSessionHasErrors('invoice_address');

	expect(CheckoutSession::invoiceAddress($user))->toBeNull();
});

/**
 * A session outlives the row it names. Deleting the address in another tab must
 * not leave the next step quoting it.
 */
it('forgets an address that has since been deleted', function () {
	$user = buyer();
	$address = UserAddress::factory()->for($user)->create();
	CheckoutSession::setInvoiceAddress($address);

	$address->delete();

	expect(CheckoutSession::invoiceAddress($user))->toBeNull();
});

it('creates an address from the dialog and selects it', function () {
	$user = buyer();

	$this->actingAs($user)
		->post('/de/checkout/address/new', [
			'first_name' => 'Jean',
			'last_name' => 'Hofmann',
			'street' => 'Betschartplatz',
			'street_no' => '118',
			'zip' => '7204',
			'city' => 'Ilanz',
			'country_code' => 'ch',
		])
		->assertRedirect('/de/checkout/address');

	$address = $user->addresses()->sole();

	expect($address->city)->toBe('Ilanz')
		->and(CheckoutSession::invoiceAddress($user)?->id)->toBe($address->id);
});

/**
 * The dialog has to reopen on a validation error, or the redirect lands on a
 * closed dialog with the messages hidden behind it. The view keys that on the
 * form's own fields being in the error bag.
 */
it('sends the dialog’s errors back so it can reopen itself', function () {
	$user = buyer();

	$this->actingAs($user)
		->post('/de/checkout/address/new', ['first_name' => 'Jean'])
		->assertSessionHasErrors(['street', 'zip', 'city', 'country_code']);

	expect($user->addresses()->count())->toBe(0);

	// And the step it lands back on opens the dialog, with what was typed
	// still in it. An `invoice_address` error is the step's own and must not
	// open it — that one is a toast.
	$page = $this->actingAs($user)
		->from('/de/checkout/address')
		->followingRedirects()
		->post('/de/checkout/address/new', ['first_name' => 'Jean'])
		->assertOk();

	expect($page->getContent())->toContain('dialog: true')
		->and($page->getContent())->toContain('value="Jean"');
});

it('does not open the dialog for an error the step itself raised', function () {
	$user = buyer();
	$theirs = UserAddress::factory()->for(User::factory())->create();

	$page = $this->actingAs($user)
		->from('/de/checkout/address')
		->followingRedirects()
		->post('/de/checkout/address', ['invoice_address' => $theirs->uuid])
		->assertOk()
		->assertSee('Bitte Rechnungsadresse auswählen');

	expect($page->getContent())->toContain('dialog: false');
});
