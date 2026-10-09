<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\ProjectSchema;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The dashboard's Vorhaben form ([[04-content]]), in the shape
 * [[ProjectFormResource]] hands out. The copy is written as `['de' => …]`, so
 * any English stays where it is.
 */
class SaveProjectRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From [[ProjectSchema]], the form's one declaration. */
	public function rules(): array
	{
		return (new ProjectSchema)->rules();
	}

	public function attributes(): array
	{
		return (new ProjectSchema)->attributes();
	}

	/** @return array<string, mixed> */
	public function projectAttributes(): array
	{
		$data = $this->validated();
		$de = fn (string $field) => ['de' => blank($data[$field] ?? null) ? null : $data[$field]];

		return [
			'title' => ['de' => $data['title']],
			'teaser' => $de('teaser'),
			'lead' => $de('lead'),
			'text' => $de('text'),
			'seo_description' => $de('seo_description'),
			'seo_tags' => $de('seo_tags'),
			'publish' => (bool) ($data['publish'] ?? false),
		];
	}

	/** @return array<int, string> the picked courses' uuids, in order */
	public function courses(): array
	{
		return array_values($this->validated()['courses'] ?? []);
	}

	/** @return array<int, string> the picked software's uuids, in order */
	public function software(): array
	{
		return array_values($this->validated()['software'] ?? []);
	}
}
