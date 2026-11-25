<?php

declare(strict_types=1);

use App\Console\Commands\Port\PortShop;
use App\Models\Country;
use App\Models\LicenceOrder;
use App\Models\LicenceVariant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * `port:shop` ([[PortShop]]) against a Shopware database cut down to the
 * columns the port reads, in memory.
 */
beforeEach(function () {
	config(['database.connections.shop' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
	DB::purge('shop');
	$schema = Schema::connection('shop');

	$schema->create('order', function (Blueprint $t) {
		$t->binary('id');
		$t->binary('version_id');
		$t->binary('state_id');
		$t->string('order_number');
		$t->binary('billing_address_id');
		$t->string('order_date_time');
		$t->decimal('amount_total');
		$t->text('customer_comment')->nullable();
		$t->string('updated_at')->nullable();
	});
	$schema->create('order_customer', function (Blueprint $t) {
		$t->binary('order_id');
		$t->binary('version_id');
		$t->binary('customer_id')->nullable();
		$t->string('email');
		$t->string('first_name');
		$t->string('last_name');
		$t->string('company')->nullable();
	});
	$schema->create('order_address', function (Blueprint $t) {
		$t->binary('id');
		$t->binary('version_id');
		$t->binary('country_id');
		$t->string('first_name');
		$t->string('last_name');
		$t->string('company')->nullable();
		$t->string('street');
		$t->string('zipcode');
		$t->string('city');
		$t->string('phone_number')->nullable();
	});
	foreach (['order_transaction', 'order_delivery'] as $table) {
		$schema->create($table, function (Blueprint $t) {
			$t->binary('id');
			$t->binary('order_id');
			$t->binary('version_id');
			$t->binary('state_id');
		});
	}
	$schema->create('order_line_item', function (Blueprint $t) {
		$t->binary('order_id');
		$t->binary('version_id');
		$t->string('label');
		$t->integer('quantity');
		$t->decimal('unit_price');
		$t->text('payload')->nullable();
		$t->integer('position');
	});
	$schema->create('customer', function (Blueprint $t) {
		$t->binary('id');
		$t->string('password')->nullable();
		$t->string('last_login')->nullable();
		$t->string('updated_at')->nullable();
		$t->string('created_at');
	});
	$schema->create('state_machine_state', function (Blueprint $t) {
		$t->binary('id');
		$t->string('technical_name');
	});
	$schema->create('state_machine_history', function (Blueprint $t) {
		$t->text('entity_id');
		$t->binary('to_state_id');
		$t->string('created_at');
	});
	$schema->create('country', function (Blueprint $t) {
		$t->binary('id');
		$t->string('iso');
	});

	$shop = DB::connection('shop');
	foreach (['open', 'in_progress', 'completed', 'cancelled', 'paid', 'shipped'] as $name) {
		$shop->table('state_machine_state')->insert(['id' => shopId("state-{$name}"), 'technical_name' => $name]);
	}
	$shop->table('country')->insert(['id' => shopId('country-ch'), 'iso' => 'CH']);
});

function shopId(string $name): string
{
	return md5($name, true);
}

/**
 * One order with its customer, address, payment, delivery and lines.
 *
 * @param  array<int, array{0: string, 1: string|null, 2: float, 3?: int}>  $lines  label, product number, net unit price, quantity
 */
function shopOrder(int $number, string $date, string $email, array $lines, string $order = 'open', string $pay = 'open', string $send = 'open', ?string $customer = null, string $total = '100.00'): void
{
	$shop = DB::connection('shop');
	$live = hex2bin(PortShop::LIVE_VERSION);
	$id = shopId("order-{$number}");

	$shop->table('order')->insert([
		'id' => $id, 'version_id' => $live, 'state_id' => shopId("state-{$order}"), 'order_number' => (string) $number,
		'billing_address_id' => shopId("address-{$number}"), 'order_date_time' => $date, 'amount_total' => $total,
		'customer_comment' => "Kommentar {$number}", 'updated_at' => $date,
	]);
	$shop->table('order_customer')->insert([
		'order_id' => $id, 'version_id' => $live, 'customer_id' => $customer ? shopId($customer) : null,
		'email' => $email, 'first_name' => 'Ada', 'last_name' => 'Muster', 'company' => null,
	]);
	$shop->table('order_address')->insert([
		'id' => shopId("address-{$number}"), 'version_id' => $live, 'country_id' => shopId('country-ch'),
		'first_name' => 'Ada', 'last_name' => 'Muster', 'company' => 'Muster AG', 'street' => 'Hardturmstrasse 66', 'zipcode' => '8005', 'city' => 'Zürich',
	]);
	$shop->table('order_transaction')->insert(['id' => shopId("tx-{$number}"), 'order_id' => $id, 'version_id' => $live, 'state_id' => shopId("state-{$pay}")]);
	$shop->table('order_delivery')->insert(['id' => shopId("dl-{$number}"), 'order_id' => $id, 'version_id' => $live, 'state_id' => shopId("state-{$send}")]);

	foreach ($lines as $position => [$label, $sku, $price]) {
		$shop->table('order_line_item')->insert([
			'order_id' => $id, 'version_id' => $live, 'label' => $label, 'quantity' => $lines[$position][3] ?? 1,
			'unit_price' => $price, 'payload' => $sku ? json_encode(['productNumber' => $sku]) : null, 'position' => $position + 1,
		]);
	}
}

it('puts an order on the VIAK account with that email, keeping the shop number and raising no invoice', function () {
	$user = User::factory()->create(['email' => 'ada@example.ch', 'city' => 'Bern']);
	$variant = LicenceVariant::factory()->create(['sku' => 'TWN-1061']);
	shopOrder(13000, '2026-11-20 10:00:00.000', 'Ada@Example.ch', [['Twinmotion (Jahresmietlizenz)', 'TWN_1061', 400.0, 2], ['Rabatt 10%', null, -80.0]], 'completed', 'paid', 'shipped', total: '778.32');

	$this->artisan('port:shop', ['--report' => storage_path('framework/testing/shop.csv')])->assertSuccessful();

	$order = LicenceOrder::with('items')->sole();
	expect($order->number)->toBe('013000')
		->and($order->user_id)->toBe($user->id)
		->and($order->invoice_id)->toBeNull()
		->and((string) $order->total)->toBe('778.32')
		->and($order->comment)->toBe('Kommentar 13000')
		->and($order->payment())->toBe('paid')
		->and($order->invoice_address['street'])->toBe('Hardturmstrasse')
		->and($order->invoice_address['street_no'])->toBe('66')
		->and($order->items)->toHaveCount(2)
		->and($order->items[0]->licence_variant_id)->toBe($variant->id)
		->and($order->items[0]->sku)->toBe('TWN-1061')
		->and($order->items[0]->quantity)->toBe(2)
		->and((string) $order->items[1]->price)->toBe('-80.00')
		->and($order->items->every(fn ($item) => $item->dispatched_at !== null))->toBeTrue();

	// The account's own data stays as VIAK has it.
	expect($user->fresh()->city)->toBe('Bern');
});

it('makes an account for a customer VIAK does not know, carrying a registered customer\'s shop password', function () {
	Country::create(['code' => 'ch', 'name' => ['de' => 'Schweiz'], 'order' => 1]);
	$hash = Hash::make('shop-secret');
	DB::connection('shop')->table('customer')->insert(['id' => shopId('cust-1'), 'password' => $hash, 'created_at' => '2025-01-01 00:00:00']);
	shopOrder(12000, '2026-01-10 09:00:00.000', 'neu@example.ch', [['Rhinoceros 8', 'RHN_1002', 995.0]], 'completed', 'paid', 'shipped', customer: 'cust-1');
	shopOrder(12001, '2026-02-10 09:00:00.000', 'guest@example.ch', [['Rhinoceros 8 Demo', 'RHN_1099', 0.0]], 'completed', 'open', 'shipped', total: '0.00');

	$this->artisan('port:shop', ['--report' => storage_path('framework/testing/shop.csv')])->assertSuccessful();

	$user = User::where('email', 'neu@example.ch')->sole();
	expect(Hash::check('shop-secret', $user->password))->toBeTrue()
		->and($user->hasVerifiedEmail())->toBeTrue()
		->and($user->company)->toBe('Muster AG')
		->and($user->street_no)->toBe('66')
		->and($user->country_code)->toBe('ch');

	$guest = User::where('email', 'guest@example.ch')->sole();
	expect(Hash::check('shop-secret', $guest->password))->toBeFalse()
		->and($guest->licenceOrders()->sole()->payment())->toBe('free');
});

it('counts old open orders as sent and paid, keeps recent ones open, and lists both', function () {
	$csv = storage_path('framework/testing/shop.csv');
	shopOrder(10010, '2021-03-01 09:00:00.000', 'old@example.ch', [['V-Ray', 'VRY_1001', 500.0]]);
	shopOrder(13050, '2026-11-24 09:00:00.000', 'new@example.ch', [['V-Ray', 'VRY_1001', 500.0]]);
	shopOrder(13051, '2026-11-25 09:00:00.000', 'gone@example.ch', [['V-Ray', 'VRY_1001', 500.0]], 'cancelled', 'paid', 'open');

	$this->artisan('port:shop', ['--report' => $csv])->assertSuccessful();

	$old = LicenceOrder::where('number', '010010')->with('items')->sole();
	expect($old->payment())->toBe('paid')->and($old->items[0]->dispatched_at)->not->toBeNull();

	$new = LicenceOrder::where('number', '013050')->sole();
	expect($new->payment())->toBe('open')
		->and(LicenceOrder::outstanding()->pluck('number')->all())->toBe(['013050']);

	$gone = LicenceOrder::where('number', '013051')->sole();
	expect($gone->payment())->toBe('cancelled')->and($gone->isPaid())->toBeFalse();

	$report = file_get_contents($csv);
	expect($report)->toStartWith("\u{FEFF}Bestellung;")
		->toContain('010010;01.03.2021')->toContain('als versendet angenommen; als bezahlt angenommen')
		->toContain('013050')->toContain('Zahlung offen übernommen')
		->toContain('storniert, im Shop bezahlt');
});

it('imports again over its own orders without doubling them, and a dry run writes nothing', function () {
	shopOrder(12500, '2026-06-01 09:00:00.000', 'ada@example.ch', [['Lumion Pro', 'LUM_1003', 1500.0]], 'completed', 'paid', 'shipped');
	$csv = ['--report' => storage_path('framework/testing/shop.csv')];

	$this->artisan('port:shop', ['--dry-run' => true] + $csv)->assertSuccessful();
	expect(LicenceOrder::count())->toBe(0)->and(User::count())->toBe(0);

	$this->artisan('port:shop', $csv)->assertSuccessful();
	$this->artisan('port:shop', $csv)->assertSuccessful();

	expect(LicenceOrder::count())->toBe(1)->and(User::count())->toBe(1);
});

it('refuses when an order entered here already holds a shop number', function () {
	LicenceOrder::create(['number' => '012500', 'user_id' => User::factory()->create()->id]);
	shopOrder(12500, '2026-06-01 09:00:00.000', 'ada@example.ch', [['Lumion Pro', 'LUM_1003', 1500.0]]);

	$this->artisan('port:shop', ['--report' => storage_path('framework/testing/shop.csv')])->assertFailed();
});

it('splits a street from its number at either end', function (string $street, ?string $name, ?string $number) {
	expect(PortShop::splitStreet($street))->toBe([$name, $number]);
})->with([
	['Hardturmstrasse 66', 'Hardturmstrasse', '66'],
	['Bahnhofstr. 12a', 'Bahnhofstr.', '12a'],
	['Route de Lausanne 3-5', 'Route de Lausanne', '3-5'],
	['68 Rue du vieux-Pont', 'Rue du vieux-Pont', '68'],
	['Looserstrasse', 'Looserstrasse', null],
	['', null, null],
]);
