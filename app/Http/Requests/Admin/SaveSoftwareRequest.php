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
	/** The editor's fields, cleaned before they are stored ([[EditorHtml]]). */
	public const RICH = ['short_description', 'full_description', 'information'];

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
			'subtitle' => $de($data['subtitle'] ?? null),
			'seo_description' => $de($data['seo_description'] ?? null),
			'seo_tags' => $de($data['seo_tags'] ?? null),
			'publish' => (bool) ($data['publish'] ?? false),
		];

		foreach (self::RICH as $field) {
			$attributes[$field] = $de(EditorHtml::sanitize($data[$field] ?? null));
		}

		return $attributes;
	}

	/** @return array<int, int> the ticked categories' ids */
	public function categoryIds(): array
	{
		return Category::query()->whereIn('uuid', $this->validated('categories', []))->pluck('id')->all();
	}
}
