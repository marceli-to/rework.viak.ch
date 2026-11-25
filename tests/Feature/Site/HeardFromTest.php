<?php

declare(strict_types=1);

use App\Models\User;

/**
 * *Wie wurdest du auf uns aufmerksam?* on the confirmation page
 * ([[11-tracking]] step 4): asked once per customer, stored on the user.
 */
beforeEach(function () {
	$this->user = User::factory()->create(['email_verified_at' => now()]);
});

it('asks a customer who has not answered', function () {
	$this->actingAs($this->user)->get('/de/checkout/confirmation')
		->assertOk()
		->assertSee('Wie bist Du auf uns aufmerksam geworden?')
		->assertSee('Google-Suche');
});

it('stores the answer on the customer and thanks them', function () {
	$this->actingAs($this->user)->post('/de/checkout/confirmation', ['heard_from' => 'google'])
		->assertRedirect('/de/checkout/confirmation#aufmerksam');

	$this->user->refresh();
	expect($this->user->heard_from)->toBe('google')
		->and($this->user->heard_from_other)->toBeNull()
		->and($this->user->heard_from_at)->not->toBeNull();

	$this->followingRedirects()->actingAs($this->user)
		->post('/de/checkout/confirmation', ['heard_from' => 'linkedin'])
		->assertSee('Danke für Deine Antwort.');

	expect($this->user->refresh()->heard_from)->toBe('google');
});

it('does not ask again once answered', function () {
	$this->user->forceFill(['heard_from' => 'social', 'heard_from_at' => now()])->save();

	$this->actingAs($this->user)->get('/de/checkout/confirmation')
		->assertOk()
		->assertDontSee('Wie bist Du auf uns aufmerksam geworden?');
});

it('keeps the text behind Andere, and asks for it', function () {
	$this->actingAs($this->user)->post('/de/checkout/confirmation', ['heard_from' => 'andere', 'heard_from_other' => null])
		->assertSessionHasErrors('heard_from_other');

	$this->actingAs($this->user)->post('/de/checkout/confirmation', ['heard_from' => 'andere', 'heard_from_other' => ' Messe Swissbau '])
		->assertRedirect();

	expect($this->user->refresh())
		->heard_from->toBe('andere')
		->heard_from_other->toBe('Messe Swissbau');
});

it('refuses an answer that is not on the list', function () {
	$this->actingAs($this->user)->post('/de/checkout/confirmation', ['heard_from' => 'fernsehen'])
		->assertSessionHasErrors('heard_from');

	expect($this->user->refresh()->heard_from)->toBeNull();
});
