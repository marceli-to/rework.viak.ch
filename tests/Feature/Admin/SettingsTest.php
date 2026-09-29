<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Course;
use App\Models\Event;
use App\Models\Location;
use App\Models\Tag;
use App\Models\User;

/**
 * *Einstellungen* — `/api/admin/settings` ([[07-dashboard]], step 6): the
 * four terms on one form, places on another.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

it('keeps settings to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/settings')->assertForbidden();
});

it('lists every kind, with what uses each term', function () {
	$category = Category::create(['title' => ['de' => 'Architektur'], 'order' => 1]);
	Course::factory()->create()->categories()->attach($category);

	$data = $this->actingAs($this->admin)->getJson('/api/admin/settings')->json('data');

	expect(array_keys($data))->toBe(['categories', 'languages', 'levels', 'tags', 'locations'])
		->and($data['categories'][0])->toMatchArray(['title' => 'Architektur', 'usage' => 1]);
});

it('adds a term at the end of its list, published', function () {
	Tag::create(['title' => ['de' => 'Alt'], 'order' => 7]);

	$uuid = $this->actingAs($this->admin)->postJson('/api/admin/settings/tags', ['title' => 'Rendering'])->assertCreated()->json('data.uuid');

	expect(Tag::where('uuid', $uuid)->first())->order->toBe(8)->publish->toBeTrue();
});

it('writes German and leaves the English where it was', function () {
	$tag = Tag::create(['title' => ['de' => 'Alt', 'en' => 'Old'], 'order' => 1]);

	$this->actingAs($this->admin)->putJson("/api/admin/settings/tags/{$tag->uuid}", ['title' => 'Neu'])->assertOk();

	expect($tag->refresh()->getTranslations('title'))->toBe(['de' => 'Neu', 'en' => 'Old']);
});

it('sends back exactly what it loads, a place too', function () {
	$location = Location::create(['description' => ['de' => 'VIAK, Zürich'], 'address' => ['de' => "Limmatstrasse 291\n8005 Zürich"], 'map' => 'https://maps.example.test/x', 'publish' => true]);

	$form = $this->actingAs($this->admin)->getJson("/api/admin/settings/locations/{$location->uuid}")->json('data');

	expect($this->putJson("/api/admin/settings/locations/{$location->uuid}", $form)->assertOk()->json('data'))->toBe($form);
});

it('asks for a name, and a real link for the map', function () {
	$this->actingAs($this->admin)->postJson('/api/admin/settings/categories', ['title' => ''])
		->assertJsonPath('errors.title.0', 'Bezeichnung muss ausgefüllt sein.');

	$this->postJson('/api/admin/settings/locations', ['description' => 'Bern', 'address' => 'Marktgasse 1', 'map' => 'goo.gl/x', 'publish' => true])
		->assertJsonPath('errors.map.0', 'Bitte einen vollständigen Link erfassen, mit https://.');
});

it('knows no other kind', function () {
	$this->actingAs($this->admin)->postJson('/api/admin/settings/software', ['title' => 'Rhino'])->assertNotFound();
});

it('refuses to delete a term a course is filed under, or a place a date is at', function () {
	$category = Category::create(['title' => ['de' => 'Architektur'], 'order' => 1]);
	Course::factory()->create()->categories()->attach($category);
	$location = Location::create(['description' => ['de' => 'Zürich'], 'address' => ['de' => 'x'], 'publish' => true]);
	Event::factory()->create(['location_id' => $location->id]);

	$this->actingAs($this->admin)->deleteJson("/api/admin/settings/categories/{$category->uuid}")->assertStatus(422);
	$this->deleteJson("/api/admin/settings/locations/{$location->uuid}")->assertStatus(422);

	expect(Category::find($category->id))->not->toBeNull();
});

it('deletes an unused term softly', function () {
	$tag = Tag::create(['title' => ['de' => 'Weg'], 'order' => 1]);

	$this->actingAs($this->admin)->deleteJson("/api/admin/settings/tags/{$tag->uuid}")->assertNoContent();

	expect(Tag::withTrashed()->find($tag->id)->trashed())->toBeTrue();
});
