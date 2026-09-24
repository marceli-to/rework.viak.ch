<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseVideo;
use App\Models\Event;
use App\Models\Language;
use App\Models\Level;
use App\Models\Software;
use App\Models\User;

/**
 * The dashboard's course form — `/api/admin/courses/{course}` and its
 * neighbours ([[07-dashboard]]).
 *
 * Two guarantees moved here from `CourseApiTest` with the endpoint: a course
 * number is never reused, and the slug survives a new title. The number itself
 * is typed now, as in legacy (Marcel, 2026-09-24). The rest are the form's own: what it loads is what it saves, the
 * English stays, the HTML is cleaned, the videos save with it, and a course
 * with bookings cannot be deleted.
 */
function coursePayload(array $overrides = []): array
{
	return [
		'number' => 77,
		'title' => 'Rhino Grundkurs',
		'subtitle' => 'mit Tom Pawlofsky',
		'fee' => '890',
		'online' => false,
		'publish' => true,
		'short_description' => '<p>Kurz.</p>',
		'categories' => [Category::create(['title' => ['de' => '3D']])->uuid],
		'languages' => [Language::create(['title' => ['de' => 'Deutsch']])->uuid],
		'levels' => [Level::create(['title' => ['de' => 'Einsteiger']])->uuid],
		...$overrides,
	];
}

beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

it('keeps the form to admins', function () {
	$this->actingAs(User::factory()->student()->create())
		->postJson('/api/admin/courses', coursePayload())
		->assertForbidden();
});

it('creates a course with the number typed, and derives the slug', function () {
	$response = $this->actingAs($this->admin)->postJson('/api/admin/courses', coursePayload(['number' => 42]))->assertCreated();

	$course = Course::where('uuid', $response->json('data.uuid'))->first();

	expect($response->json('data.number'))->toBe(42)
		->and($course->getTranslation('slug', 'de'))->toBe('rhino-grundkurs')
		->and((string) $course->fee)->toBe('890.00');
});

it('offers the next free number, counting deleted courses', function () {
	Course::factory()->create(['number' => 12])->delete();

	$this->actingAs($this->admin)->getJson('/api/admin/courses/options')->assertJsonPath('data.next_number', 13);
});

/** Numbers are on invoices through `Event::number()`, so none comes back. */
it('refuses a number another course has, deleted ones included', function () {
	Course::factory()->create(['number' => 12])->delete();

	$this->actingAs($this->admin)
		->postJson('/api/admin/courses', coursePayload(['number' => 12]))
		->assertJsonPath('errors.number.0', 'Diese Nummer ist bereits vergeben, auch gelöschte Kurse behalten ihre.');
});

it('lets a course keep its own number, or change it to a free one', function () {
	$course = Course::factory()->create(['number' => 20]);

	$this->actingAs($this->admin)->putJson("/api/admin/courses/{$course->uuid}", coursePayload(['number' => 20]))->assertOk();
	$this->putJson("/api/admin/courses/{$course->uuid}", coursePayload(['number' => 21]))->assertJsonPath('data.number', 21);
});

it('keeps the slug when the title changes', function () {
	$course = Course::factory()->create(['title' => ['de' => 'Alter Titel'], 'slug' => ['de' => 'alter-titel']]);

	$this->actingAs($this->admin)->putJson("/api/admin/courses/{$course->uuid}", coursePayload(['title' => 'Ganz neuer Titel']))->assertOk();

	expect($course->refresh()->getTranslation('slug', 'de'))->toBe('alter-titel');
});

it('sends back exactly what it loads', function () {
	$course = Course::factory()->create();
	$form = $this->actingAs($this->admin)->putJson("/api/admin/courses/{$course->uuid}", coursePayload())->json('data');

	$again = $this->putJson("/api/admin/courses/{$course->uuid}", $form)->assertOk()->json('data');

	expect($again)->toBe($form);
});

it('picks taxonomies by uuid and syncs them', function () {
	$course = Course::factory()->create();
	$rhino = Software::create(['title' => ['de' => 'Rhino']]);
	$blender = Software::create(['title' => ['de' => 'Blender']]);

	$this->actingAs($this->admin)->putJson("/api/admin/courses/{$course->uuid}", coursePayload(['software' => [$rhino->uuid, $blender->uuid]]));
	expect($course->refresh()->software)->toHaveCount(2);

	$this->putJson("/api/admin/courses/{$course->uuid}", coursePayload(['software' => [$rhino->uuid]]))
		->assertJsonPath('data.software', [$rhino->uuid]);
});

it('asks for a category, a language and a level, in German', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/courses', coursePayload(['categories' => [], 'languages' => [], 'levels' => []]))
		->assertUnprocessable()
		->assertJsonPath('errors.categories.0', 'Bitte mindestens eine Kategorie wählen.')
		->assertJsonValidationErrors(['languages', 'levels']);
});

it('names a field the way the form labels it', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/courses', coursePayload(['subtitle' => '']))
		->assertJsonPath('errors.subtitle.0', 'Subtitel muss ausgefüllt sein.');
});

/** The admin edits German only; the English the port carried stays ([[04-content]]). */
it('writes German and leaves the English where it was', function () {
	$course = Course::factory()->create([
		'title' => ['de' => 'Alt', 'en' => 'Old'],
		'facts' => [['de' => '<p>A</p>', 'en' => '<p>A en</p>']],
	]);

	$this->actingAs($this->admin)->putJson("/api/admin/courses/{$course->uuid}", coursePayload(['title' => 'Neu', 'facts' => ['<p>B</p>']]));

	$course->refresh();

	expect($course->getTranslation('title', 'en'))->toBe('Old')
		->and($course->getTranslation('title', 'de'))->toBe('Neu')
		->and($course->facts[0])->toBe(['de' => '<p>B</p>', 'en' => '<p>A en</p>']);
});

it('cleans the editor HTML, and keeps a link opening in a new tab', function () {
	$course = Course::factory()->create();

	$this->actingAs($this->admin)->putJson("/api/admin/courses/{$course->uuid}", coursePayload([
		'short_description' => '<p>Hallo <script>alert(1)</script><a href="javascript:alert(1)">x</a> <a href="https://viak.ch" target="_blank" rel="noopener">y</a></p><p></p>',
	]));

	expect($course->refresh()->getTranslation('short_description', 'de'))
		->toBe('<p>Hallo <a>x</a> <a href="https://viak.ch" target="_blank" rel="noopener">y</a></p>');
});

it('saves the videos with the form — added, changed, removed, in order', function () {
	$course = Course::factory()->create();
	$kept = CourseVideo::factory()->for($course)->create(['title' => ['de' => 'Alt'], 'order' => 1]);
	CourseVideo::factory()->for($course)->create(['order' => 2]);

	$this->actingAs($this->admin)->putJson("/api/admin/courses/{$course->uuid}", coursePayload(['videos' => [
		['uuid' => null, 'title' => 'Neu', 'code' => '<iframe src="https://www.youtube.com/embed/x"></iframe>', 'publish' => true],
		['uuid' => $kept->uuid, 'title' => 'Geändert', 'code' => $kept->code, 'publish' => false],
	]]))->assertOk();

	$videos = $course->videos()->ordered()->get();

	expect($videos)->toHaveCount(2)
		->and($videos->map(fn ($video) => $video->getTranslation('title', 'de'))->all())->toBe(['Neu', 'Geändert'])
		->and($videos[1]->uuid)->toBe($kept->uuid)
		->and($videos[1]->publish)->toBeFalse();
});

it('refuses to delete a course whose dates have bookings, cancelled ones included', function () {
	$course = Course::factory()->create();
	Booking::factory()->for(Event::factory()->for($course))->create(['cancelled_at' => now()]);

	$this->actingAs($this->admin)
		->deleteJson("/api/admin/courses/{$course->uuid}")
		->assertUnprocessable();

	expect($course->fresh())->not->toBeNull();
});

it('deletes a course without bookings, and its dates with it', function () {
	$course = Course::factory()->create();
	$event = Event::factory()->for($course)->create();

	$this->actingAs($this->admin)->deleteJson("/api/admin/courses/{$course->uuid}")->assertNoContent();

	expect(Course::find($course->id))->toBeNull()
		->and(Event::find($event->id))->toBeNull()
		->and(Event::withTrashed()->find($event->id))->not->toBeNull();
});

it('offers every term of the five taxonomies, by title', function () {
	Software::create(['title' => ['de' => 'Twinmotion']]);
	Software::create(['title' => ['de' => 'Blender']]);

	expect(array_column($this->actingAs($this->admin)->getJson('/api/admin/courses/options')->json('data.software'), 'title'))
		->toBe(['Blender', 'Twinmotion']);
});
