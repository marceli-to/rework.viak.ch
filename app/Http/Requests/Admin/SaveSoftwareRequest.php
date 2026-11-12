<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\SoftwareSchema;
use App\Models\Category;
use App\Models\Software;
use App\Support\EditorHtml;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The dashboard's software form ([[05-licences]]), in the shape
 * [[SoftwareFormResource]] hands out, written as [[SaveCourseRequest]]
 * writes a course: `['de' => …]`, so any English stays where it is.
 */
class SaveSoftwareRequest extends FormRequest
{
	/**
	 * The editor's fields, cleaned before they are stored ([[EditorHtml]]).
	 * *Subtitel* and *Weitere Informationen* left the form on 2026-11-09 and
	 * are not written: what they hold stays.
	 */
	public const RICH = ['short_description', 'full_description'];

	/** The Angebot page's plain texts (#70), trimmed, empty as null. */
	public const PLAIN = ['audiences', 'intro', 'courses_intro', 'licences_intro', 'quote'];

	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From [[SoftwareSchema]], the form's one declaration. */
	public function rules(): array
	{
		return (new SoftwareSchema)->rules($this->route('software') instanceof Software ? $this->route('software') : null);
	}

	public function messages(): array
	{
		return (new SoftwareSchema)->messages();
	}

	public function attributes(): array
	{
		return (new SoftwareSchema)->attributes();
	}

	/** @return array<string, mixed> */
	public function softwareAttributes(): array
	{
		$data = $this->validated();
		$de = fn (?string $value) => ['de' => blank($value) ? null : $value];

		$attributes = [
			'title' => ['de' => $data['title']],
			'licence_note' => $de(isset($data['licence_note']) ? trim($data['licence_note']) : null),
			'seo_description' => $de($data['seo_description'] ?? null),
			'seo_tags' => $de($data['seo_tags'] ?? null),
			'publish' => (bool) ($data['publish'] ?? false),
			'featured' => (bool) ($data['featured'] ?? false),
			'plugin' => (bool) ($data['plugin'] ?? false),
		];

		foreach (self::RICH as $field) {
			$attributes[$field] = $de(EditorHtml::sanitize($data[$field] ?? null));
		}

		foreach (self::PLAIN as $field) {
			$attributes[$field] = $de(isset($data[$field]) ? trim($data[$field]) : null);
		}

		return $attributes;
	}

	/** @return array<int, int> *Läuft in*, never itself */
	public function hostIds(): array
	{
		$self = $this->route('software');

		return Software::query()->whereIn('uuid', $this->validated('hosts', []))
			->when($self instanceof Software, fn ($query) => $query->whereKeyNot($self->getKey()))
			->pluck('id')->all();
	}

	/** @return array<int, int> the ticked categories' ids */
	public function categoryIds(): array
	{
		return Category::query()->whereIn('uuid', $this->validated('categories', []))->pluck('id')->all();
	}
}
