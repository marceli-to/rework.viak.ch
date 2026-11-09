<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Course;
use App\Models\LicenceProduct;
use App\Models\Software;
use App\Models\User;

/**
 * *Software bearbeiten* — `/api/admin/software` ([[05-licences]]): a
 * software's page, the form the *Software* list's pencil opens.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

function softwarePayload(array $overrides = []): array
{
	return [
		'title' => 'Rhinoceros',
		'short_description' => '<p>Rhino ist das Werkzeug.</p>',
		'full_description' => '<p>Präzision statt Polygone.</p>',
		'categories' => [],
		'publish' => true,
		'featured' => true,
		'seo_description' => 'Rhino-Lizenzen und Kurse',
		'seo_tags' => '',
		...$overrides,
	];
}

it('keeps the software form to admins', function () {
	$software = Software::create(['title' => ['de' => 'Rhinoceros']]);

	$this->actingAs(User::factory()->expert()->create())->getJson("/api/admin/software/{$software->uuid}")->assertForbidden();
	$this->actingAs(User::factory()->expert()->create())->postJson('/api/admin/software', softwarePayload())->assertForbidden();
});

it('makes the slug once, from the title, through any door', function () {
	$named = Software::create(['title' => ['de' => 'HDR Light Studio']]);
	expect($named->getTranslation('slug', 'de'))->toBe('hdr-light-studio');

	$uuid = $this->actingAs($this->admin)->postJson('/api/admin/software', softwarePayload(['title' => 'V-Ray']))
		->assertCreated()
		->assertJsonPath('data.url', '/de/angebote/v-ray')
		->json('data.uuid');

	$this->putJson("/api/admin/software/{$uuid}", softwarePayload(['title' => 'V-Ray 7']))
		->assertJsonPath('data.title', 'V-Ray 7')
		->assertJsonPath('data.url', '/de/angebote/v-ray');
});

it('saves the copy, the categories and the SEO, German only', function () {
	$software = Software::create([
		'title' => ['de' => 'Rhinoceros', 'en' => 'Rhinoceros'], 'subtitle' => ['de' => 'Alt', 'en' => 'Freeform'],
		'information' => ['de' => '<p>Info</p>'],
	]);
	$category = Category::create(['title' => ['de' => 'Architektur'], 'order' => 1, 'publish' => true]);

	$this->actingAs($this->admin)
		->putJson("/api/admin/software/{$software->uuid}", softwarePayload(['categories' => [$category->uuid]]))
		->assertOk()
		->assertJsonPath('data.categories', [$category->uuid])
		->assertJsonPath('data.seo_description', 'Rhino-Lizenzen und Kurse');

	$software->refresh();
	expect($software->featured)->toBeTrue()
		->and($software->getTranslation('subtitle', 'en'))->toBe('Freeform')
		// Off the form since 2026-11-09: a save leaves what it holds.
		->and($software->getTranslation('subtitle', 'de'))->toBe('Alt')
		->and($software->getTranslation('information', 'de'))->toBe('<p>Info</p>')
		->and($software->getTranslation('full_description', 'de'))->toBe('<p>Präzision statt Polygone.</p>');
});

it('refuses a name another software has, whatever its case', function () {
	Software::create(['title' => ['de' => 'Rhinoceros']]);

	$this->actingAs($this->admin)->postJson('/api/admin/software', softwarePayload(['title' => 'rhinoceros ']))
		->assertJsonValidationErrors(['title' => 'Diese Bezeichnung gibt es schon.']);
});

it('deletes a software only while nothing uses it', function () {
	$used = Software::create(['title' => ['de' => 'V-Ray']]);
	LicenceProduct::factory()->create(['software_id' => $used->id]);
	$taught = Software::create(['title' => ['de' => 'SketchUp']]);
	Course::factory()->create()->software()->attach($taught);
	$free = Software::create(['title' => ['de' => 'Godot']]);

	$this->actingAs($this->admin)->deleteJson("/api/admin/software/{$used->uuid}")->assertStatus(422);
	$this->deleteJson("/api/admin/software/{$taught->uuid}")->assertStatus(422);
	$this->deleteJson("/api/admin/software/{$free->uuid}")->assertNoContent();

	expect(Software::find($free->id))->toBeNull();
});

it('hands out the software form', function () {
	$this->actingAs($this->admin)->getJson('/api/admin/forms/software')
		->assertOk()
		->assertJsonPath('data.fields.0.name', 'title');
});

/** The Angebot page's new texts (#70, [[14-angebote]] *The website texts*). */
it('saves the new texts in the software form, German only, empty as nothing', function () {
	$software = Software::create(['title' => ['de' => 'Rhinoceros'], 'quote' => ['en' => 'English stays']]);

	$this->actingAs($this->admin)->putJson("/api/admin/software/{$software->uuid}", softwarePayload([
		'audiences' => '  Architektur- & Ingenieurbüros, Yachtdesign ',
		'intro' => 'Die Visualisierungs-Akademie bietet Schulungen und Lizenzen an:',
		'courses_intro' => 'Lerne Rhino in der Praxis.',
		'licences_intro' => '',
		'quote' => 'Die passende Software und das Wissen.',
	]))->assertOk()
		->assertJsonPath('data.audiences', 'Architektur- & Ingenieurbüros, Yachtdesign')
		->assertJsonPath('data.licences_intro', '')
		// *Seite ansehen* in the «Was ist …?» section, once there is a text.
		->assertJsonPath('data.about_url', '/de/angebote/rhinoceros/was-ist-rhinoceros');

	$software->refresh();

	expect($software->getTranslation('courses_intro', 'de'))->toBe('Lerne Rhino in der Praxis.')
		->and($software->getTranslation('licences_intro', 'de', false))->toBe('')
		->and($software->getTranslation('quote', 'en'))->toBe('English stays');
});
