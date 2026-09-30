<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\SiteUrl;

/**
 * The rework's role picker: an account with several areas finds them all under
 * the header's profile icon, where legacy asked on a screen after login
 * (`12-customers.md`).
 */
it('lists the areas a person may use, in the profile link\'s order', function () {
	$all = User::factory()->admin()->expert()->student()->create();

	expect(collect(SiteUrl::areasFor($all, 'de'))->pluck('label')->all())->toBe(['Student', 'Experte', 'Dashboard'])
		->and(SiteUrl::areasFor(User::factory()->student()->create(), 'de'))->toHaveCount(1)
		->and(SiteUrl::areasFor(null))->toBe([]);
});

it('makes the profile icon a menu of the areas for a multi-role account', function () {
	$this->actingAs(User::factory()->expert()->student()->create())
		->get('/de')
		->assertOk()
		->assertSee('aria-haspopup="true"', false)
		->assertSee('href="'.SiteUrl::studentPortal('de').'"', false)
		->assertSee('href="'.SiteUrl::expertPortal('de').'"', false)
		->assertDontSee('href="/dashboard"', false);
});

it('keeps the plain profile link for an account with one area', function () {
	$this->actingAs(User::factory()->student()->create())
		->get('/de')
		->assertOk()
		->assertDontSee('aria-haspopup="true"', false);
});

it('offers an admin the other areas in the dashboard shell', function () {
	$admin = User::factory()->admin()->expert()->create();

	$html = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

	preg_match('/<meta name="areas" content="([^"]*)"/', $html, $match);
	expect(json_decode(html_entity_decode($match[1]), true))->toBe([
		['key' => 'expert', 'label' => 'Experte', 'href' => SiteUrl::expertPortal()],
	]);
});
