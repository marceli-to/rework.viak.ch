<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Enums\Role;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEventRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->can('create', Event::class) ?? false;
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			'course_uuid' => ['required', 'uuid', Rule::exists('courses', 'uuid')],
			'location_uuid' => ['nullable', 'uuid', Rule::exists('locations', 'uuid')],

			'dates' => ['required', 'array', 'min:1'],
			'dates.*.date' => ['required', 'date'],
			'dates.*.time_start' => ['nullable', 'date_format:H:i'],
			'dates.*.time_end' => ['nullable', 'date_format:H:i', 'after:dates.*.time_start'],

			'registration_until' => ['nullable', 'date'],
			'min_participants' => ['required', 'integer', 'min:1', 'max:999'],
			'max_participants' => ['required', 'integer', 'min:1', 'max:999'],

			'fee' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
			'free_of_charge' => ['boolean'],
			'rentals_available' => ['boolean'],
			'online' => ['boolean'],
			'publish' => ['boolean'],

			'expert_uuids' => ['array'],
			'expert_uuids.*' => ['uuid', Rule::exists('users', 'uuid')],
		];
	}

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator): void {
			if ($this->integer('max_participants') < $this->integer('min_participants')) {
				$validator->errors()->add(
					'max_participants',
					'The maximum number of participants cannot be below the minimum.'
				);
			}

			// An event whose registration closes after it starts would let
			// students book a course that has already run.
			$first = collect($this->input('dates', []))->pluck('date')->sort()->first();

			if ($first && $this->input('registration_until') && $this->date('registration_until')?->gt($first)) {
				$validator->errors()->add(
					'registration_until',
					'Registration cannot stay open past the first course date.'
				);
			}
		});
	}

	public function resolvedCourse(): Course
	{
		return Course::where('uuid', $this->string('course_uuid'))->firstOrFail();
	}

	/** @return array<string, mixed> */
	public function eventAttributes(): array
	{
		$attributes = $this->safe()->except(['course_uuid', 'location_uuid', 'dates', 'expert_uuids']);

		if ($this->filled('location_uuid')) {
			$attributes['location_id'] = \App\Models\Location::where('uuid', $this->string('location_uuid'))->value('id');
		}

		return $attributes;
	}

	/** @return array<int, array{date: string, time_start?: ?string, time_end?: ?string}> */
	public function dates(): array
	{
		return $this->input('dates', []);
	}

	/**
	 * Only users who actually teach may be attached as experts — an admin-only
	 * endpoint is still no reason to let an arbitrary user id through.
	 *
	 * @return array<int, int>
	 */
	public function expertIds(): array
	{
		$uuids = $this->input('expert_uuids', []);

		if ($uuids === []) {
			return [];
		}

		return User::whereIn('uuid', $uuids)
			->where('role', '!=', Role::Student->value)
			->pluck('id')
			->all();
	}
}
