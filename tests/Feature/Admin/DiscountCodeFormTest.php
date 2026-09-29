<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Checkout;
use App\Models\DiscountCode;
use App\Models\User;
use App\Support\DiscountCodeGenerator;

/**
 * *Rabatt-Codes* — `/api/admin/discount-codes` ([[07-dashboard]], step 6).
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

function codePayload(array $overrides = []): array
{
	return [
		'code' => app(DiscountCodeGenerator::class)->next(),
		'amount' => '50',
		'type' => 'fixed',
		'valid_from' => '',
		'valid_to' => '',
		'usage_limit' => 1,
		'remarks' => '',
		...$overrides,
	];
}

it('keeps discount codes to admins', function () {
	$this->actingAs(User::factory()->student()->create())->getJson('/api/admin/discount-codes')->assertForbidden();
});

it('generates legacy-shaped codes, never one that exists, deleted ones included', function () {
	$code = app(DiscountCodeGenerator::class)->next();

	expect($code)->toMatch('/^VIAK-[A-HJKMNP-Z2-9]{4}-[A-HJKMNP-Z2-9]{4}$/');

	DiscountCode::factory()->create(['code' => $code])->delete();

	$this->actingAs($this->admin)->postJson('/api/admin/discount-codes', codePayload(['code' => $code]))->assertJsonValidationErrors('code');
});

it('offers a new code already generated, good for one order', function () {
	$defaults = $this->actingAs($this->admin)->getJson('/api/admin/forms/discount-code')->json('data.defaults');

	expect($defaults['code'])->toStartWith('VIAK-')
		->and($defaults['usage_limit'])->toBe(1);
});

it('creates a code, an empty limit meaning unlimited', function () {
	$uuid = $this->actingAs($this->admin)
		->postJson('/api/admin/discount-codes', codePayload(['usage_limit' => '', 'valid_from' => '2026-10-01', 'valid_to' => '2026-12-31']))
		->assertCreated()
		->json('data.uuid');

	expect(DiscountCode::where('uuid', $uuid)->first())
		->usage_limit->toBeNull()
		->valid_to->toDateString()->toBe('2026-12-31');
});

it('sends back exactly what it loads', function () {
	$code = DiscountCode::factory()->create(['code' => app(DiscountCodeGenerator::class)->next(), 'usage_limit' => 3, 'valid_from' => '2026-01-01', 'valid_to' => '2026-12-31']);

	$form = $this->actingAs($this->admin)->getJson("/api/admin/discount-codes/{$code->uuid}")->json('data');

	expect($this->putJson("/api/admin/discount-codes/{$code->uuid}", $form)->assertOk()->json('data'))->toBe($form);
});

it('refuses more than 100 percent, and an end before the start', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/discount-codes', codePayload(['type' => 'percent', 'amount' => '120', 'valid_from' => '2026-12-01', 'valid_to' => '2026-11-01']))
		->assertJsonPath('errors.amount.0', 'Höchstens 100 Prozent.')
		->assertJsonPath('errors.valid_to.0', 'Endet vor dem Beginn.');
});

it('refuses a code typed by hand in another shape', function () {
	$this->actingAs($this->admin)->postJson('/api/admin/discount-codes', codePayload(['code' => 'GRATIS']))->assertJsonValidationErrors('code');
});

it('lists every code newest first, with how often each was used', function () {
	$old = DiscountCode::factory()->create(['usage_limit' => 1]);
	Checkout::factory()->create(['discount_code_id' => $old->id]);
	Booking::factory()->create(['discount_code_id' => $old->id, 'checkout_id' => null]);
	$new = DiscountCode::factory()->create();

	$rows = $this->actingAs($this->admin)->getJson('/api/admin/discount-codes')->json('data');

	expect(array_column($rows, 'uuid'))->toBe([$new->uuid, $old->uuid])
		->and($rows[1]['times_used'])->toBe(2)
		->and($rows[1]['redeemable'])->toBeFalse()
		->and($rows[0]['redeemable'])->toBeTrue();
});

it('deletes softly, and a deleted code is no longer redeemable', function () {
	$code = DiscountCode::factory()->create();

	$this->actingAs($this->admin)->deleteJson("/api/admin/discount-codes/{$code->uuid}")->assertNoContent();

	expect(DiscountCode::withTrashed()->find($code->id))->trashed()->toBeTrue()
		->isRedeemableOn(today())->toBeFalse();
});
