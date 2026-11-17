<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\NewsSchema;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The dashboard's news form ([[News]]), in the shape [[NewsFormResource]]
 * hands out. Title and text written as `['de' => …]`; *Kein Link* is null.
 */
class SaveNewsRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	public function rules(): array
	{
		return (new NewsSchema)->rules();
	}

	public function attributes(): array
	{
		return (new NewsSchema)->attributes();
	}

	/** @return array<string, mixed> */
	public function newsAttributes(): array
	{
		$data = $this->validated();
		$text = trim((string) ($data['text'] ?? ''));

		return [
			'title' => ['de' => trim($data['title'])],
			'text' => ['de' => $text === '' ? null : $text],
			'link' => filled($data['link'] ?? null) ? $data['link'] : null,
			'publish' => (bool) ($data['publish'] ?? false),
		];
	}
}
