<?php

declare(strict_types=1);

use App\Enums\DiscountType;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Event;

/**
 * 183 of 710 production bookings are cancelled — a quarter. An event's
 * capacity that counted them would report every course as far fuller than it
 * is, and would turn people away from seats that are free.
 */
it('leaves cancelled bookings out of the active scope', function () {
	$event = Event::factory()->create();

	Booking::factory()->for($event)->count(3)->create();
	Booking::factory()->for($event)->cancelled()->count(2)->create();

	expect(Booking::active()->count())->toBe(3)
		->and(Booking::count())->toBe(5);
});

it('subtracts the discount from the course fee', function () {
	$booking = Booking::factory()->create([
		'course_fee' => '499.00',
		'discount_amount' => '50.00',
	]);

	expect($booking->netFee())->toBe('449.00');
});

it('reads a percentage discount as a share of the fee, not francs', function () {
	$code = DiscountCode::factory()->percent('10.00')->create();

	expect($code->amountOff('499.00'))->toBe('49.90');
});

it('reads a fixed discount as francs', function () {
	$code = DiscountCode::factory()->create(['type' => DiscountType::Fixed, 'amount' => '50.00']);

	expect($code->amountOff('499.00'))->toBe('50.00');
});

/**
 * Legacy carried `fix` and `percent` as separate booleans, so "both" and
 * "neither" were representable and meaningless. The enum makes them unsayable.
 */
it('stores a discount type that cannot be both at once', function () {
	$code = DiscountCode::factory()->percent()->create();

	expect($code->fresh()->type)->toBe(DiscountType::Percent);
});

it('treats an open-ended validity window as still valid', function () {
	DiscountCode::factory()->create(['code' => 'ALWAYS', 'valid_from' => null, 'valid_to' => null]);
	DiscountCode::factory()->create(['code' => 'EXPIRED', 'valid_from' => null, 'valid_to' => '2020-01-01']);
	DiscountCode::factory()->create(['code' => 'FUTURE', 'valid_from' => '2099-01-01', 'valid_to' => null]);

	$valid = DiscountCode::validOn(now())->pluck('code');

	expect($valid)->toContain('ALWAYS')
		->and($valid)->not->toContain('EXPIRED')
		->and($valid)->not->toContain('FUTURE');
});

/**
 * Booking 000640 spent a CHF 50 voucher that was deleted a month later. The
 * code is ported soft-deleted so the booking can still say what it spent —
 * but a deleted voucher must never be redeemable again, which is what the
 * SoftDeletes global scope is doing here. Both halves matter.
 */
it('keeps a booking linked to a discount code that was later deleted', function () {
	$code = DiscountCode::factory()->create(['code' => 'GONE']);
	$booking = Booking::factory()->create(['discount_code_id' => $code->id, 'discount_amount' => '50.00']);

	$code->delete();

	expect($booking->fresh()->discountCode()->withTrashed()->first()->code)->toBe('GONE')
		->and(DiscountCode::where('code', 'GONE')->exists())->toBeFalse()
		->and(DiscountCode::validOn(now())->pluck('code'))->not->toContain('GONE');
});

it('keeps the frozen invoice address as structured data', function () {
	$booking = Booking::factory()->create([
		'invoice_address' => ['lines' => ['Antonia Haller', 'Kaiserstr. 76', '7752 Orsières']],
	]);

	expect($booking->fresh()->invoice_address['lines'])->toHaveCount(3);
});
