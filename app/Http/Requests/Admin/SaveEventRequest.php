<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\EventSchema;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The dashboard's course-date form ([[07-dashboard]], step 6), the shape
 * [[EventFormResource]] hands out. Replaces the public API's
 * `StoreEventRequest`, whose rules it keeps, in German.
 */
class SaveEventRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From [[EventSchema]], the form's one declaration. */
	public function rules(): array
	{
		return (new EventSchema)->rules($this->route('event'));
	}

	public function messages(): array
	{
		return (new EventSchema)->messages();
	}

	public function attributes(): array
	{
		return (new EventSchema)->attributes();
	}

	/** What one field cannot see alone. */
	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator): void {
			$failed = $validator->errors();

			if (! $failed->hasAny(['min_participants', 'max_participants'])
				&& $this->integer('max_participants') < $this->integer('min_participants')) {
				$failed->add('max_participants', 'Darf nicht kleiner sein als min. Teilnehmer.');
			}

			// Registration closing after the course starts would let students
			// book a course that has already run.
			if ($failed->hasAny(['registration_until', 'dates', 'dates.*.date']) || ! $this->filled('registration_until')) {
				return;
			}

			$first = collect($this->input('dates', []))->pluck('date')->filter()->sort()->first();

			if ($first && $this->input('registration_until') > $first) {
				$failed->add('registration_until', 'Darf nicht nach dem ersten Kurstag liegen.');
			}
		});
	}

	/** @return array<string, mixed> */
	public function eventAttributes(): array
	{
		$data = $this->validated();

		return [
			'registration_until' => $data['registration_until'] ?? null,
			'min_participants' => (int) $data['min_participants'],
			'max_participants' => (int) $data['max_participants'],
			'rentals_available' => (int) $data['rentals_available'],
			'fee' => ($data['fee'] ?? null) === null ? null : $data['fee'],
			'online' => (bool) ($data['online'] ?? false),
			'free_of_charge' => (bool) ($data['free_of_charge'] ?? false),
			'publish' => (bool) ($data['publish'] ?? false),
			'location_id' => Location::query()->where('uuid', $data['location'])->value('id'),
		];
	}

	/** @return array<int, array{date: string, time_start: ?string, time_end: ?string}> */
	public function dates(): array
	{
		return collect($this->validated('dates'))->map(fn (array $day) => [
			'date' => $day['date'],
			'time_start' => $day['time_start'] ?? null,
			'time_end' => $day['time_end'] ?? null,
		])->all();
	}

	/**
	 * The schema lets only Expert-role uuids through ([[EventSchema]]), so
	 * these are experts.
	 *
	 * @return array<int, int>
	 */
	public function expertIds(): array
	{
		return User::query()->whereIn('uuid', $this->validated('experts', []))->pluck('id')->all();
	}
}
