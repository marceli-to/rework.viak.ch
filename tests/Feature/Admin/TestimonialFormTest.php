<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Software;
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

it('lists them in the order they were entered', function () {
	$a = Testimonial::factory()->create(['order' => 1]);
	$b = Testimonial::factory()->create(['order' => 2]);

	expect(array_column($this->actingAs($this->admin)->getJson('/api/admin/testimonials')->json('data'), 'uuid'))->toBe([$a->uuid, $b->uuid]);
});

it('has no order of its own to save', function () {
	$this->actingAs($this->admin)->patchJson('/api/admin/testimonials/order', ['testimonials' => []])->assertStatus(405);
});

it('deletes one', function () {
	$testimonial = Testimonial::factory()->create();

	$this->actingAs($this->admin)->deleteJson("/api/admin/testimonials/{$testimonial->uuid}")->assertNoContent();

	expect(Testimonial::find($testimonial->id))->toBeNull();
});

/**
 * What a testimonial is about — the hint a page's picker shows beside it
 * (Marcel, 2026-09-24). A course, a software, or nothing: VIAK as a whole.
 */
it('takes a course or a software as its subject, or none', function () {
	$course = Course::factory()->create(['number' => 14, 'title' => ['de' => 'SketchUp Kurs']]);
	$rhino = Software::create(['title' => ['de' => 'Rhinoceros']]);

	$this->actingAs($this->admin);

	$aboutCourse = $this->postJson('/api/admin/testimonials', testimonialPayload(['subject' => "course:{$course->uuid}"]))->json('data');
	$aboutRhino = $this->postJson('/api/admin/testimonials', testimonialPayload(['subject' => "software:{$rhino->uuid}"]))->json('data');
	$general = $this->postJson('/api/admin/testimonials', testimonialPayload(['subject' => '']))->json('data');

	expect($aboutCourse['subject'])->toBe("course:{$course->uuid}")
		->and($aboutCourse['subject_label'])->toBe('14 SketchUp Kurs')
		->and($aboutRhino['subject_label'])->toBe('Rhinoceros')
		->and($general['subject'])->toBe('')
		->and($general['subject_label'])->toBe('Allgemein')
		->and(Testimonial::where('uuid', $aboutCourse['uuid'])->first()->subject->is($course))->toBeTrue();
});

it('refuses a subject that is not on the list', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/testimonials', testimonialPayload(['subject' => 'course:nicht-da']))
		->assertJsonValidationErrors('subject');
});

it('offers the subjects grouped, courses by number, then software', function () {
	Course::factory()->create(['number' => 20, 'title' => ['de' => 'Zwanzig']]);
	Course::factory()->create(['number' => 3, 'title' => ['de' => 'Drei']]);
	Software::create(['title' => ['de' => 'Twinmotion']]);
	Software::create(['title' => ['de' => 'Blender']]);

	$field = collect($this->actingAs($this->admin)->getJson('/api/admin/forms/testimonial')->json('data.fields'))->firstWhere('name', 'subject');

	expect($field['placeholder'])->toBe('Allgemein (die VIAK als Ganzes)')
		->and(array_column($field['options'], 'label'))->toBe(['Kurse', 'Software'])
		->and(array_column($field['options'][0]['options'], 'label'))->toBe(['3 Drei', '20 Zwanzig'])
		->and(array_column($field['options'][1]['options'], 'label'))->toBe(['Blender', 'Twinmotion']);
});
