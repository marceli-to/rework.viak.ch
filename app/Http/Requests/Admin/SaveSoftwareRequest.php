<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\SoftwareSchema;
use App\Models\Category;
use App\Models\Software;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The dashboard's software form ([[05-licences]]), in the shape
 * [[SoftwareFormResource]] hands out, written as [[SaveCourseRequest]]
 * writes a course: `['de' => …]`, so any English stays where it is.
 *
 * The record only: its pages' texts are saved by [[SaveSoftwarePageRequest]]
 * since 2026-11-17, and a save here leaves them as they are.
 */
class SaveSoftwareRequest extends FormRequest
{
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

		return [
			'title' => ['de' => $data['title']],
			'publish' => (bool) ($data['publish'] ?? false),
			'featured' => (bool) ($data['featured'] ?? false),
			'plugin' => (bool) ($data['plugin'] ?? false),
		];
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
