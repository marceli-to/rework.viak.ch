<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\CompanyCourseSchema;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

/** *Firmenkurs* ([[16-firmenkurse]]), the shape [[CompanyCourseFormResource]] hands out. */
class SaveCompanyCourseRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From [[CompanyCourseSchema]], the form's one declaration. */
	public function rules(): array
	{
		return (new CompanyCourseSchema)->rules($this->route('companyCourse'));
	}

	public function messages(): array
	{
		return (new CompanyCourseSchema)->messages();
	}

	public function attributes(): array
	{
		return (new CompanyCourseSchema)->attributes();
	}

	/** @return array<string, mixed> */
	public function courseAttributes(): array
	{
		$data = $this->validated();
		$optional = fn (string $key) => ($data[$key] ?? '') === '' || ($data[$key] ?? null) === null ? null : $data[$key];

		return [
			'company' => $data['company'],
			'first_name' => $optional('first_name'),
			'last_name' => $optional('last_name'),
			'street' => $data['street'],
			'street_no' => $optional('street_no'),
			'zip' => $data['zip'],
			'city' => $data['city'],
			'country_code' => $data['country'],
			// The select takes only its own options, so the course is found.
			'course_id' => $optional('course') ? Course::query()->where('uuid', $data['course'])->value('id') : null,
			'title' => $optional('title'),
			'location' => $optional('location'),
			'participants' => $optional('participants'),
			'email' => $data['email'],
			'remarks' => $optional('remarks'),
		];
	}

	/** @return array<int, array{date: string, time_start: ?string, time_end: ?string}> */
	public function dates(): array
	{
		return collect($this->validated('dates'))->map(fn (array $day) => [
			'date' => $day['date'],
			'time_start' => ($day['time_start'] ?? '') ?: null,
			'time_end' => ($day['time_end'] ?? '') ?: null,
		])->all();
	}

	/** @return array<int, array{position: int, description: string, net: string, taxed: bool}> */
	public function lines(): array
	{
		return collect($this->validated('lines') ?? [])->values()->map(fn (array $line, int $index) => [
			'position' => $index + 1,
			'description' => $line['description'],
			'net' => number_format((float) $line['net'], 2, '.', ''),
			'taxed' => (bool) ($line['taxed'] ?? false),
		])->all();
	}
}
