<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The dashboard's testimonial form ([[07-dashboard]]) — the second of the two
 * forms the field kit is extracted from.
 *
 * The form's own shape, as [[TestimonialFormResource]] hands it out: German
 * strings, written as `['de' => …]` so any English stays where it is.
 */
class SaveTestimonialRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	public function rules(): array
	{
		return [
			'quote' => ['required', 'string', 'max:1000'],
			'name' => ['required', 'string', 'max:255'],
			'context' => ['nullable', 'string', 'max:255'],
			'featured' => ['boolean'],
			'publish' => ['boolean'],
		];
	}

	public function attributes(): array
	{
		return ['quote' => 'Zitat', 'name' => 'Name', 'context' => 'Firma, Ort'];
	}

	/** @return array<string, mixed> */
	public function testimonialAttributes(): array
	{
		$data = $this->validated();

		return [
			'quote' => ['de' => $data['quote']],
			'name' => $data['name'],
			'context' => ['de' => ($data['context'] ?? '') === '' ? null : $data['context']],
			'featured' => (bool) ($data['featured'] ?? false),
			'publish' => (bool) ($data['publish'] ?? false),
		];
	}
}
