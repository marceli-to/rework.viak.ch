<?php

declare(strict_types=1);

namespace App\Http\Requests\Courses;

use App\Enums\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->can('update', $this->route('course')) ?? false;
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			'title' => ['sometimes', 'array'],
			'title.de' => ['required_with:title', 'string', 'max:255'],
			'title.en' => ['nullable', 'string', 'max:255'],

			'subtitle' => ['nullable', 'array'],
			'summary' => ['nullable', 'array'],
			'short_description' => ['nullable', 'array'],
			'full_description' => ['nullable', 'array'],
			'information_booking' => ['nullable', 'array'],
			'information_content' => ['nullable', 'array'],
			'seo_description' => ['nullable', 'array'],
			'seo_tags' => ['nullable', 'array'],

			'facts' => ['nullable', 'array', 'max:3'],
			'facts.*' => ['array'],

			'fee' => ['sometimes', 'numeric', 'min:0', 'max:99999.99'],
			'online' => ['boolean'],
			'publish' => ['boolean'],
			'order' => ['integer'],

			'categories' => ['array'],
			'categories.*' => ['integer', Rule::exists('categories', 'id')],
			'levels' => ['array'],
			'levels.*' => ['integer', Rule::exists('levels', 'id')],
			'languages' => ['array'],
			'languages.*' => ['integer', Rule::exists('languages', 'id')],
			'software' => ['array'],
			'software.*' => ['integer', Rule::exists('software', 'id')],
			'tags' => ['array'],
			'tags.*' => ['integer', Rule::exists('tags', 'id')],
		];
	}

	/**
	 * Only the attributes the model owns — taxonomy ids are synced separately.
	 *
	 * @return array<string, mixed>
	 */
	public function courseAttributes(): array
	{
		return $this->safe()->except(['categories', 'levels', 'languages', 'software', 'tags']);
	}

	/** @return array<string, array<int, int>> */
	public function taxonomies(): array
	{
		return [
			'categories' => $this->input('categories', []),
			'levels' => $this->input('levels', []),
			'languages' => $this->input('languages', []),
			'software' => $this->input('software', []),
			'tags' => $this->input('tags', []),
		];
	}

	/** Strip translations for locales we do not publish. */
	protected function prepareForValidation(): void
	{
		$allowed = Locale::values();

		foreach (['title', 'subtitle', 'summary', 'short_description', 'full_description', 'information_booking', 'information_content', 'seo_description', 'seo_tags'] as $field) {
			if (is_array($value = $this->input($field))) {
				$this->merge([$field => array_intersect_key($value, array_flip($allowed))]);
			}
		}
	}
}
