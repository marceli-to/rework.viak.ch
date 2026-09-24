<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\CourseSchema;
use App\Models\Category;
use App\Models\Course;
use App\Models\Language;
use App\Models\Level;
use App\Models\Software;
use App\Models\Tag;
use App\Support\EditorHtml;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The dashboard's course form, create and edit alike ([[07-dashboard]]).
 *
 * **The payload is the form's own shape**, the one [[CourseFormResource]]
 * hands out: German strings for the texts, uuids for the taxonomies, SEO flat,
 * three facts, the videos inline. What the form loads is what it saves.
 *
 * **What is valid is declared in [[CourseSchema]]**, with the labels the
 * messages use — the field kit's one place for them. This request keeps what
 * only it knows: how the values are written.
 */
class SaveCourseRequest extends FormRequest
{
	/** The editor's fields — cleaned before they are stored ([[EditorHtml]]). */
	public const RICH = ['short_description', 'full_description', 'information_booking', 'information_content', 'summary'];

	/** Relation => the taxonomy it picks from. */
	public const TAXONOMIES = [
		'categories' => Category::class,
		'languages' => Language::class,
		'levels' => Level::class,
		'software' => Software::class,
		'tags' => Tag::class,
	];

	public function authorize(): bool
	{
		$course = $this->route('course');

		return $course instanceof Course
			? $this->user()?->can('update', $course) ?? false
			: $this->user()?->can('create', Course::class) ?? false;
	}

	/** From [[CourseSchema]], the form's one declaration. */
	public function rules(): array
	{
		return $this->schema()->rules($this->route('course') instanceof Course ? $this->route('course') : null);
	}

	public function messages(): array
	{
		return $this->schema()->messages();
	}

	public function attributes(): array
	{
		return $this->schema()->attributes();
	}

	private function schema(): CourseSchema
	{
		return new CourseSchema;
	}

	/**
	 * The course's own columns, German only.
	 *
	 * A translatable field is written as `['de' => …]`, which spatie merges
	 * into what is there — so the English the port carried across stays put
	 * (`04-content.md`, *the admin edits DE only*). The facts are a plain JSON
	 * list of `{de, en}` maps, so the same is done for them by hand.
	 *
	 * @return array<string, mixed>
	 */
	public function courseAttributes(?Course $course = null): array
	{
		$data = $this->validated();
		$de = fn (?string $value) => ['de' => $value === '' ? null : $value];

		$attributes = [
			'number' => (int) $data['number'],
			'title' => $de($data['title']),
			'subtitle' => $de($data['subtitle']),
			'seo_description' => $de($data['seo_description'] ?? null),
			'seo_tags' => $de($data['seo_tags'] ?? null),
			'fee' => $data['fee'],
			'online' => (bool) ($data['online'] ?? false),
			'publish' => (bool) ($data['publish'] ?? false),
		];

		foreach (self::RICH as $field) {
			$attributes[$field] = $de(EditorHtml::sanitize($data[$field] ?? null));
		}

		$existing = array_values($course?->facts ?? []);
		$attributes['facts'] = collect(array_pad($data['facts'] ?? [], 3, null))
			->take(3)
			->map(fn (?string $html, int $index) => [
				...(is_array($existing[$index] ?? null) ? $existing[$index] : []),
				'de' => EditorHtml::sanitize($html),
			])
			->all();

		return $attributes;
	}

	/** @return array<string, array<int, string>> relation => uuids */
	public function taxonomies(): array
	{
		return collect(self::TAXONOMIES)
			->map(fn (string $model, string $relation) => $this->validated($relation, []))
			->all();
	}

	/** @return array<int, array{uuid: ?string, title: ?string, code: string, publish: bool}> */
	public function videos(): array
	{
		return $this->validated('videos', []);
	}
}
