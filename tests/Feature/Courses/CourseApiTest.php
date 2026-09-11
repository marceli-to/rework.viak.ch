<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Software;
use App\Models\User;

it('lists only published courses to the public', function () {
	Course::factory()->create(['title' => ['de' => 'Sichtbar']]);
	Course::factory()->unpublished()->create(['title' => ['de' => 'Versteckt']]);

	$response = $this->getJson('/api/courses')->assertOk();

	expect($response->json('data'))->toHaveCount(1)
		->and($response->json('data.0.title.de'))->toBe('Sichtbar');
});

it('lists unpublished courses to an admin', function () {
	Course::factory()->create();
	Course::factory()->unpublished()->create();

	$response = $this->actingAs(User::factory()->admin()->create())
		->getJson('/api/courses')
		->assertOk();

	expect($response->json('data'))->toHaveCount(2);
});

it('hides an unpublished course from a guest', function () {
	$course = Course::factory()->unpublished()->create();

	$this->getJson("/api/courses/{$course->uuid}")->assertForbidden();
});

it('refuses to create a course for a student', function () {
	$this->actingAs(User::factory()->create())
		->postJson('/api/courses', ['title' => ['de' => 'Neu'], 'fee' => 100])
		->assertForbidden();
});

it('creates a course, assigning the number and slug itself', function () {
	Course::factory()->create(['number' => 41]);

	$response = $this->actingAs(User::factory()->admin()->create())
		->postJson('/api/courses', [
			'title' => ['de' => 'Rhino Grundkurs', 'en' => 'Rhino Basics'],
			'fee' => 890,
			'publish' => true,
		])
		->assertCreated();

	expect($response->json('data.number'))->toBe(42)
		->and($response->json('data.slug.de'))->toBe('rhino-grundkurs')
		->and($response->json('data.fee'))->toBe('890.00');
});

it('ignores a client-supplied course number', function () {
	Course::factory()->create(['number' => 7]);

	$response = $this->actingAs(User::factory()->admin()->create())
		->postJson('/api/courses', ['title' => ['de' => 'X'], 'fee' => 10, 'number' => 999])
		->assertCreated();

	expect($response->json('data.number'))->toBe(8);
});

it('does not reuse the number of a soft-deleted course', function () {
	Course::factory()->create(['number' => 12])->delete();

	$response = $this->actingAs(User::factory()->admin()->create())
		->postJson('/api/courses', ['title' => ['de' => 'Y'], 'fee' => 10])
		->assertCreated();

	expect($response->json('data.number'))->toBe(13);
});

it('keeps the slug stable when the title changes', function () {
	$course = Course::factory()->create([
		'title' => ['de' => 'Alter Titel'],
		'slug' => ['de' => 'alter-titel'],
	]);

	$this->actingAs(User::factory()->admin()->create())
		->putJson("/api/courses/{$course->uuid}", ['title' => ['de' => 'Ganz Neuer Titel']])
		->assertOk()
		->assertJsonPath('data.slug.de', 'alter-titel');
});

it('syncs taxonomies on update', function () {
	$course = Course::factory()->create();
	$rhino = Software::create(['title' => ['de' => 'Rhino']]);
	$blender = Software::create(['title' => ['de' => 'Blender']]);

	$this->actingAs(User::factory()->admin()->create())
		->putJson("/api/courses/{$course->uuid}", ['software' => [$rhino->id, $blender->id]])
		->assertOk();

	expect($course->refresh()->software)->toHaveCount(2);

	$this->actingAs(User::factory()->admin()->create())
		->putJson("/api/courses/{$course->uuid}", ['software' => [$rhino->id]])
		->assertOk();

	expect($course->refresh()->software)->toHaveCount(1);
});

it('rejects a translation for a locale we do not publish', function () {
	$response = $this->actingAs(User::factory()->admin()->create())
		->postJson('/api/courses', [
			'title' => ['de' => 'Titel', 'fr' => 'Titre'],
			'fee' => 100,
		])
		->assertCreated();

	expect($response->json('data.title'))->not->toHaveKey('fr');
});
