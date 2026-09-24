<?php

declare(strict_types=1);

use App\Models\Testimonial;
use App\Models\User;

/**
 * *Seiteninhalte → Testimonials* — `/api/admin/testimonials` ([[07-dashboard]]),
 * the second form the field kit is extracted from.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

function testimonialPayload(array $overrides = []): array
{
	return [
		'quote' => 'Der Rhino-Kurs hat mir gezeigt, wie ich meine Ideen professionell umsetze.',
		'name' => 'Anna Muster',
		'context' => 'Architekturbüro, Zürich',
		'publish' => true,
		...$overrides,
	];
}

it('keeps testimonials to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/testimonials')->assertForbidden();
	$this->actingAs(User::factory()->expert()->create())->postJson('/api/admin/testimonials', testimonialPayload())->assertForbidden();
});

it('creates one at the end of the list', function () {
	Testimonial::factory()->create(['order' => 4]);

	$uuid = $this->actingAs($this->admin)->postJson('/api/admin/testimonials', testimonialPayload())->assertCreated()->json('data.uuid');

	expect(Testimonial::where('uuid', $uuid)->first())
		->order->toBe(5)
		->and(Testimonial::where('uuid', $uuid)->first()->getTranslation('quote', 'de'))->toStartWith('Der Rhino-Kurs');
});

it('sends back exactly what it loads', function () {
	$testimonial = Testimonial::factory()->create();
	$form = $this->actingAs($this->admin)->getJson("/api/admin/testimonials/{$testimonial->uuid}")->json('data');

	expect($this->putJson("/api/admin/testimonials/{$testimonial->uuid}", $form)->assertOk()->json('data'))->toBe($form);
});

it('writes German and leaves the English where it was', function () {
	$testimonial = Testimonial::factory()->create(['quote' => ['de' => 'Alt', 'en' => 'Old']]);

	$this->actingAs($this->admin)->putJson("/api/admin/testimonials/{$testimonial->uuid}", testimonialPayload(['quote' => 'Neu']));

	expect($testimonial->refresh()->getTranslation('quote', 'en'))->toBe('Old')
		->and($testimonial->getTranslation('quote', 'de'))->toBe('Neu');
});

it('asks for the quote and the name, in German', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/testimonials', testimonialPayload(['quote' => '', 'name' => '']))
		->assertJsonPath('errors.quote.0', 'Zitat muss ausgefüllt sein.')
		->assertJsonPath('errors.name.0', 'Name muss ausgefüllt sein.');
});

it('lists them in their order, and saves a new one', function () {
	$a = Testimonial::factory()->create(['order' => 1]);
	$b = Testimonial::factory()->create(['order' => 2]);

	expect(array_column($this->actingAs($this->admin)->getJson('/api/admin/testimonials')->json('data'), 'uuid'))->toBe([$a->uuid, $b->uuid]);

	$this->patchJson('/api/admin/testimonials/order', ['testimonials' => [$b->uuid, $a->uuid]])->assertNoContent();

	expect(array_column($this->getJson('/api/admin/testimonials')->json('data'), 'uuid'))->toBe([$b->uuid, $a->uuid]);
});

it('deletes one', function () {
	$testimonial = Testimonial::factory()->create();

	$this->actingAs($this->admin)->deleteJson("/api/admin/testimonials/{$testimonial->uuid}")->assertNoContent();

	expect(Testimonial::find($testimonial->id))->toBeNull();
});
