<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Project;
use App\Models\User;

/**
 * *Seiteninhalte → Vorhaben* — `/api/admin/projects` ([[04-content]]): the
 * homepage's tiles and the page each one opens.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

function projectPayload(array $overrides = []): array
{
	return [
		'title' => 'Räume visualisieren',
		'teaser' => 'Enscape, Twinmotion, Lumion, V-Ray',
		'lead' => 'Ein Projekt überzeugend zeigen.',
		'text' => '<p>Für Architekt:innen.</p>',
		'courses' => [],
		'publish' => true,
		...$overrides,
	];
}

it('keeps the Vorhaben to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/projects')->assertForbidden();
	$this->actingAs(User::factory()->expert()->create())->postJson('/api/admin/projects', projectPayload())->assertForbidden();
});

it('creates one at the end of the list, its slug from the title', function () {
	Project::factory()->create(['slug' => ['de' => 'anderes-vorhaben'], 'order' => 4]);

	$uuid = $this->actingAs($this->admin)->postJson('/api/admin/projects', projectPayload(['title' => 'Mit KI gestalten']))
		->assertCreated()
		->assertJsonPath('data.url', '/de/vorhaben/mit-ki-gestalten')
		->json('data.uuid');

	expect(Project::where('uuid', $uuid)->first())->order->toBe(5);
});

it('numbers a slug another Vorhaben already has', function () {
	Project::factory()->create(['title' => ['de' => 'Bilder gestalten'], 'slug' => ['de' => 'bilder-gestalten']]);

	$this->actingAs($this->admin)->postJson('/api/admin/projects', projectPayload(['title' => 'Bilder gestalten']))
		->assertJsonPath('data.url', '/de/vorhaben/bilder-gestalten-2');
});

it('keeps the URL when the title changes', function () {
	$project = Project::factory()->create(['title' => ['de' => 'Alt'], 'slug' => ['de' => 'alt']]);

	$this->actingAs($this->admin)->putJson("/api/admin/projects/{$project->uuid}", projectPayload(['title' => 'Neu']))
		->assertJsonPath('data.title', 'Neu')
		->assertJsonPath('data.url', '/de/vorhaben/alt');
});

it('saves the courses in the order they were picked', function () {
	[$a, $b, $c] = Course::factory()->count(3)->create();
	$project = Project::factory()->create();

	$this->actingAs($this->admin)
		->putJson("/api/admin/projects/{$project->uuid}", projectPayload(['courses' => [$c->uuid, $a->uuid]]))
		->assertJsonPath('data.courses', [$c->uuid, $a->uuid]);

	$this->putJson("/api/admin/projects/{$project->uuid}", projectPayload(['courses' => [$b->uuid, $c->uuid]]));

	expect($project->courses()->pluck('courses.uuid')->all())->toBe([$b->uuid, $c->uuid]);
});

it('refuses a course that does not exist, or one twice', function () {
	$course = Course::factory()->create();

	$this->actingAs($this->admin)
		->postJson('/api/admin/projects', projectPayload(['courses' => ['not-a-course']]))
		->assertJsonValidationErrors('courses.0');

	$this->postJson('/api/admin/projects', projectPayload(['courses' => [$course->uuid, $course->uuid]]))
		->assertJsonValidationErrors('courses.0');
});

it('sends back exactly what it loads', function () {
	$project = Project::factory()->create();
	$project->courses()->attach(Course::factory()->create(), ['order' => 1]);
	$form = $this->actingAs($this->admin)->getJson("/api/admin/projects/{$project->uuid}")->json('data');

	expect($this->putJson("/api/admin/projects/{$project->uuid}", $form)->assertOk()->json('data'))->toBe($form);
});

it('asks for the title, in German, and lets the rest be empty', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/projects', projectPayload(['title' => '']))
		->assertJsonPath('errors.title.0', 'Titel muss ausgefüllt sein.');

	$this->postJson('/api/admin/projects', ['title' => 'Nur ein Titel', 'teaser' => null, 'lead' => null, 'text' => null, 'courses' => [], 'publish' => false])
		->assertCreated();
});

it('lists them in their order, and saves a new one', function () {
	$a = Project::factory()->create(['order' => 1]);
	$b = Project::factory()->create(['order' => 2]);

	$this->actingAs($this->admin);
	expect(array_column($this->getJson('/api/admin/projects')->json('data'), 'uuid'))->toBe([$a->uuid, $b->uuid]);

	$this->postJson('/api/admin/projects/order', ['projects' => [$b->uuid, $a->uuid]])->assertNoContent();

	expect(array_column($this->getJson('/api/admin/projects')->json('data'), 'uuid'))->toBe([$b->uuid, $a->uuid]);
});

it('deletes one and leaves its courses', function () {
	$course = Course::factory()->create();
	$project = Project::factory()->create();
	$project->courses()->attach($course, ['order' => 1]);

	$this->actingAs($this->admin)->deleteJson("/api/admin/projects/{$project->uuid}")->assertNoContent();

	expect(Project::count())->toBe(0)
		->and($course->fresh())->not->toBeNull();
});

it('serves the form with every course to pick from', function () {
	Course::factory()->unpublished()->create(['title' => ['de' => 'Entwurf']]);

	$offers = collect($this->actingAs($this->admin)->getJson('/api/admin/forms/project')->json('data.fields'))->firstWhere('name', 'courses');

	expect($offers['type'])->toBe('offers')
		->and($offers['options'][0])->toMatchArray(['label' => 'Entwurf', 'publish' => false]);
});
