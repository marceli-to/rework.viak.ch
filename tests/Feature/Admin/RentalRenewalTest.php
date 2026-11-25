<?php

declare(strict_types=1);

use App\Enums\LicenceType;
use App\Mail\RentalRenewalReminder;
use App\Models\LicenceOrder;
use App\Models\LicenceOrderItem;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\User;
use App\Support\RentalRenewals;
use App\Support\SiteUrl;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

/**
 * *Mietlizenzen, die bald auslaufen* (#83, [[RentalRenewals]]): rentals whose
 * year ends soon, until ordered again, and the reminder sent by hand.
 */
beforeEach(function () {
	$this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now(), 'first_name' => 'Oliver', 'last_name' => 'Büro']);
	$this->customer = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Muster']);
	$this->product = LicenceProduct::factory()->create(['title' => ['de' => 'Twinmotion']]);
	$this->rental = LicenceVariant::factory()->for($this->product, 'product')
		->create(['title' => ['de' => 'Jahresmietlizenz'], 'licence_type' => LicenceType::Subscription, 'price' => '500.00']);
	$this->perpetual = LicenceVariant::factory()->for($this->product, 'product')
		->create(['title' => ['de' => 'Dauerlizenz'], 'licence_type' => LicenceType::Perpetual, 'price' => '900.00']);
});

function rentalLine(User $customer, string $date, ?LicenceVariant $variant, array $line = [], array $order = []): LicenceOrderItem
{
	static $number = 100;

	$licenceOrder = LicenceOrder::create(['number' => sprintf('%06d', ++$number), 'user_id' => $customer->id, ...$order]);
	$licenceOrder->forceFill(['created_at' => $date])->save();

	return LicenceOrderItem::create([
		'licence_order_id' => $licenceOrder->id,
		'licence_variant_id' => $variant?->id,
		'title' => $variant ? 'Twinmotion '.$variant->getTranslation('title', 'de') : 'Rhino 7 Mietlizenz',
		'price' => $variant?->price ?? '300.00',
		'quantity' => 1,
		'position' => 1,
		...$line,
	]);
}

it('lists rentals whose year ends within 60 days or ended in the last 30, soonest first', function () {
	$soon = rentalLine($this->customer, '2025-12-20', $this->rental);
	$missed = rentalLine($this->customer, '2025-11-01', null);
	rentalLine($this->customer, '2025-12-20', $this->perpetual);
	rentalLine(User::factory()->create(), '2026-03-01', $this->rental);
	rentalLine($this->customer, '2025-10-01', $this->rental);
	rentalLine($this->customer, '2025-12-01', $this->rental, ['price' => '0.00']);
	rentalLine($this->customer, '2025-12-01', $this->rental, [], ['cancelled_at' => now()]);

	expect(RentalRenewals::due()->pluck('id')->all())->toBe([$missed->id, $soon->id])
		->and(RentalRenewals::endsOn($soon)->toDateString())->toBe('2026-12-20');
});

it('drops a rental once the customer ordered the same licence again', function () {
	$line = rentalLine($this->customer, '2025-12-20', $this->rental);
	rentalLine(User::factory()->create(), '2026-11-01', $this->rental);
	expect(RentalRenewals::due()->pluck('id')->all())->toBe([$line->id]);

	rentalLine($this->customer, '2026-11-20', $this->rental, [], ['cancelled_at' => now()]);
	expect(RentalRenewals::due()->pluck('id')->all())->toBe([$line->id]);

	rentalLine($this->customer, '2026-11-20', $this->rental);
	expect(RentalRenewals::due())->toBeEmpty();
});

it('shows the list to admins only', function () {
	rentalLine($this->customer, '2025-12-20', $this->rental);

	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/rental-renewals')->assertForbidden();

	$this->actingAs($this->admin)->getJson('/api/admin/rental-renewals')->assertOk()
		->assertJsonPath('data.0.line', 'Twinmotion Jahresmietlizenz')
		->assertJsonPath('data.0.ends', '2026-12-20')
		->assertJsonPath('data.0.customer.name', 'Ada Muster')
		->assertJsonPath('data.0.reminded_at', null);
});

it('sends the reminder to the customer and records when and by whom', function () {
	Mail::fake();
	$line = rentalLine($this->customer, '2025-12-20', $this->rental);

	$this->actingAs($this->admin)->postJson("/api/admin/licence-order-items/{$line->uuid}/remind")->assertOk()
		->assertJsonPath('data.reminded_at', '2026-11-25')
		->assertJsonPath('data.reminded_by', 'Oliver Büro');

	Mail::assertQueued(RentalRenewalReminder::class, fn (RentalRenewalReminder $mail) => $mail->hasTo($this->customer->email));
	expect($line->refresh()->reminded_by)->toBe($this->admin->id);
});

it('words the reminder with the end date and a link to renew', function () {
	$line = rentalLine($this->customer, '2025-12-20', $this->rental);

	expect((new RentalRenewalReminder($line))->render())->toContain('Deine Jahresmietlizenz läuft am 20.12.2026 ab')
		->toContain('Twinmotion Jahresmietlizenz')
		->toContain('Lizenz verlängern')
		->toContain(e(url(SiteUrl::licence($this->product, 'de'))));

	// A shop line with no variant on the site links the software list.
	expect((new RentalRenewalReminder(rentalLine($this->customer, '2025-12-20', null)))->render())
		->toContain(url(SiteUrl::softwareIndex('de')).'"');
});
