<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\Course;
use App\Models\Language;
use App\Models\Level;
use App\Models\Software;
use App\Models\Tag;
use App\Support\EditorHtml;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The dashboard's course form, create and edit alike ([[07-dashboard]]).
 *
 * **The payload is the form's own shape**, the one [[CourseFormResource]]
 * hands out: German strings for the texts, uuids for the taxonomies, SEO flat,
 * three facts, the videos inline. What the form loads is what it saves.
 *
 * Required as legacy requires it: title, subtitle, short description, and at
 * least one category, language and level. The number is not asked for — the
 * server assigns it and never reuses one ([[CourseNumber]]).
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

	public function rules(): array
	{
		$rules = [
			'title' => ['required', 'string', 'max:255'],
			'subtitle' => ['required', 'string', 'max:1000'],
			'fee' => ['required', 'numeric', 'min:0', 'max:99999.99'],
			'online' => ['boolean'],
			'publish' => ['boolean'],

			'short_description' => ['required', 'string'],
			'full_description' => ['nullable', 'string'],
			'information_booking' => ['nullable', 'string'],
			'information_content' => ['nullable', 'string'],
			'summary' => ['nullable', 'string'],

			'facts' => ['array', 'max:3'],
			'facts.*' => ['nullable', 'string'],

			'seo_description' => ['nullable', 'string', 'max:1000'],
			'seo_tags' => ['nullable', 'string', 'max:1000'],

			'videos' => ['array'],
			'videos.*.uuid' => ['nullable', 'string'],
			'videos.*.title' => ['nullable', 'string', 'max:255'],
			'videos.*.code' => ['required', 'string'],
			'videos.*.publish' => ['boolean'],
		];

		foreach (self::TAXONOMIES as $relation => $model) {
			$required = in_array($relation, ['categories', 'languages', 'levels'], true);
			$rules[$relation] = $required ? ['required', 'array', 'min:1'] : ['array'];
			$rules["{$relation}.*"] = ['string', Rule::exists((new $model)->getTable(), 'uuid')];
		}

		return $rules;
	}

	public function messages(): array
	{
		return [
			'categories.required' => 'Bitte mindestens eine Kategorie wählen.',
			'languages.required' => 'Bitte mindestens eine Sprache wählen.',
			'levels.required' => 'Bitte mindestens ein Level wählen.',
			'videos.*.code.required' => 'Ein Video braucht einen Code.',
		];
	}

	/** The form's labels, so a message says *Subtitel*, not *subtitle*. */
	public function attributes(): array
	{
		return [
			'title' => 'Titel',
			'subtitle' => 'Subtitel',
			'fee' => 'Kosten',
			'short_description' => 'Kurzbeschrieb',
			'full_description' => 'Detailbeschrieb',
			'information_booking' => 'Weitere Informationen',
			'information_content' => 'Weitere Informationen',
			'summary' => 'Kursbeschreibung (PDF)',
			'facts.*' => 'Facts',
			'seo_description' => 'SEO - Beschreibung',
			'seo_tags' => 'SEO - Keywords',
			'videos.*.title' => 'Titel',
			'software' => 'Software',
			'tags' => 'Tags',
		];
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
