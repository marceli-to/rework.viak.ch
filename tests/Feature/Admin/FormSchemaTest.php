<?php

declare(strict_types=1);

use App\Forms\CourseSchema;
use App\Forms\EventSchema;
use App\Forms\TestimonialSchema;
use App\Models\User;

/**
 * The field kit's schemas ([[Field]], [[07-dashboard]]) — one declaration that
 * both validates a request and draws its form.
 */
it('serves a form to admins and nobody else', function () {
	$this->getJson('/api/admin/forms/course')->assertUnauthorized();
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/forms/course')->assertForbidden();
	$this->actingAs(User::factory()->admin()->create())->getJson('/api/admin/forms/nichts')->assertNotFound();
});

it('draws what it validates: every required field is marked, every marked one required', function (string $schema, string $form) {
	$drawn = [];
	$walk = function (array $fields, string $prefix = '') use (&$walk, &$drawn): void {
		foreach ($fields as $field) {
			if (($field['required'] ?? false) && isset($field['name'])) {
				$drawn[] = $prefix.$field['name'];
			}
			$walk($field['fields'] ?? [], ($field['type'] === 'repeater') ? $prefix.$field['name'].'.*.' : $prefix);
		}
	};
	$walk($this->actingAs(User::factory()->admin()->create())->getJson("/api/admin/forms/{$form}")->json('data.fields'));

	$required = collect((new $schema)->rules())->filter(fn ($rules) => in_array('required', $rules, true))->keys()->sort()->values()->all();

	expect(collect($drawn)->sort()->values()->all())->toBe($required);
})->with([
	[CourseSchema::class, 'course'],
	[TestimonialSchema::class, 'testimonial'],
	[EventSchema::class, 'event'],
]);

it('gives every form field a starting value, so create and edit have one shape', function (string $schema) {
	$names = collect((new $schema)->rules())->keys()
		->reject(fn ($path) => str_contains($path, '*'))
		->map(fn ($path) => explode('.', $path)[0])
		->unique()->sort()->values()->all();

	expect(collect((new $schema)->defaults())->keys()->sort()->values()->all())->toBe($names);
})->with([CourseSchema::class, TestimonialSchema::class, EventSchema::class]);

it('names a repeater row’s fields for the messages', function () {
	expect((new CourseSchema)->attributes())->toMatchArray(['videos.*.title' => 'Titel', 'subtitle' => 'Subtitel'])
		->and((new CourseSchema)->messages())->toMatchArray(['videos.*.code.required' => 'Ein Video braucht einen Code.']);
});
