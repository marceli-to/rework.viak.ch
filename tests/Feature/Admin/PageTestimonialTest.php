<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\Testimonial;
use App\Models\User;

/**
 * A fixed page's testimonials, picked in the dashboard ([[Page]],
 * *Seiteninhalte → Firmenschulung*).
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

it('keeps the picker to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/pages/firmenschulung/testimonials')->assertForbidden();
});

it('knows only the pages there are', function () {
	$this->actingAs($this->admin)->getJson('/api/admin/pages/startseite-gibts-noch-nicht/testimonials')->assertNotFound();
});

it('hands out what is picked, in order, and every testimonial to pick from', function () {
	[$a, $b, $c] = Testimonial::factory()->count(3)->create();
	Page::for('firmenschulung')->testimonials()->attach([$b->id => ['order' => 1], $a->id => ['order' => 2]]);

	$data = $this->actingAs($this->admin)->getJson('/api/admin/pages/firmenschulung/testimonials')->assertOk()->json('data');

	expect($data['label'])->toBe('Firmenschulung')
		->and($data['picked'])->toBe([$b->uuid, $a->uuid])
		->and(array_column($data['testimonials'], 'uuid'))->toContain($a->uuid, $b->uuid, $c->uuid);
});

it('saves the picked list as sent, order and all, and empties it', function () {
	[$a, $b, $c] = Testimonial::factory()->count(3)->create();
	$page = Page::for('firmenschulung');
	$page->testimonials()->attach($a->id, ['order' => 1]);

	$this->actingAs($this->admin)->putJson('/api/admin/pages/firmenschulung/testimonials', ['testimonials' => [$c->uuid, $b->uuid]])->assertNoContent();

	expect($page->testimonials()->pluck('testimonials.id')->all())->toBe([$c->id, $b->id]);

	$this->putJson('/api/admin/pages/firmenschulung/testimonials', ['testimonials' => []])->assertNoContent();

	expect($page->testimonials()->count())->toBe(0);
});

it('refuses an unknown or doubled testimonial', function () {
	$a = Testimonial::factory()->create();

	$this->actingAs($this->admin)
		->putJson('/api/admin/pages/firmenschulung/testimonials', ['testimonials' => ['nicht-da', $a->uuid, $a->uuid]])
		->assertJsonValidationErrors(['testimonials.0', 'testimonials.1']);
});

it('names the page under Verwendet auf', function () {
	$a = Testimonial::factory()->create();
	Page::for('firmenschulung')->testimonials()->attach($a->id);

	$placements = $this->actingAs($this->admin)->getJson("/api/admin/testimonials/{$a->uuid}")->json('data.placements');

	expect(array_column($placements, 'label'))->toBe(['Firmenschulung']);
});
