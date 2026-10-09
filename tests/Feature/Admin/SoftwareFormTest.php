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
		'subtitle' => 'Präzises Freiform-Modellieren',
		'short_description' => '<p>Rhino ist das Werkzeug.</p>',
		'full_description' => '<p>Präzision statt Polygone.</p>',
		'information' => '',
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
		->assertJsonPath('data.url', '/de/software/v-ray')
		->json('data.uuid');

	$this->putJson("/api/admin/software/{$uuid}", softwarePayload(['title' => 'V-Ray 7']))
		->assertJsonPath('data.title', 'V-Ray 7')
		->assertJsonPath('data.url', '/de/software/v-ray');
});

it('saves the copy, the categories and the SEO, German only', function () {
	$software = Software::create(['title' => ['de' => 'Rhinoceros', 'en' => 'Rhinoceros'], 'subtitle' => ['en' => 'Freeform']]);
	$category = Category::create(['title' => ['de' => 'Architektur'], 'order' => 1, 'publish' => true]);

	$this->actingAs($this->admin)
		->putJson("/api/admin/software/{$software->uuid}", softwarePayload(['categories' => [$category->uuid]]))
		->assertOk()
		->assertJsonPath('data.subtitle', 'Präzises Freiform-Modellieren')
		->assertJsonPath('data.categories', [$category->uuid])
		->assertJsonPath('data.seo_description', 'Rhino-Lizenzen und Kurse');

	$software->refresh();
	expect($software->featured)->toBeTrue()
		->and($software->getTranslation('subtitle', 'en'))->toBe('Freeform')
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
