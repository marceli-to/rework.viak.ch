<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\TestimonialSchema;
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

	/** From [[TestimonialSchema]], the form's one declaration. */
	public function rules(): array
	{
		return (new TestimonialSchema)->rules();
	}

	public function attributes(): array
	{
		return (new TestimonialSchema)->attributes();
	}

	/** @return array<string, mixed> */
	public function testimonialAttributes(): array
	{
		$data = $this->validated();

		return [
			'quote' => ['de' => $data['quote']],
			'name' => $data['name'],
			'context' => ['de' => ($data['context'] ?? '') === '' ? null : $data['context']],
			'publish' => (bool) ($data['publish'] ?? false),
		];
	}
}
