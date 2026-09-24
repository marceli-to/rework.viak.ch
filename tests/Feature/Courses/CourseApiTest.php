<?php

declare(strict_types=1);

use App\Models\Course;
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
