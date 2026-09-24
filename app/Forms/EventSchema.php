<?php

declare(strict_types=1);

namespace App\Forms;

use App\Enums\Role;
use App\Models\Location;
use App\Models\User;

/**
 * *Kursdatum erfassen* / *bearbeiten* — legacy's event form, in its order
 * ([[07-dashboard]], step 6). Confirming, closing and cancelling are not
 * fields and not here yet: each one mails, and mail is chunk 10.
 */
final class EventSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::date('registration_until')->label('Deadline Anmeldung')
				->message('date_format', 'Bitte als TT.MM.JJJJ erfassen, mit vierstelligem Jahr.'),
			Field::row([
				Field::number('min_participants')->label('min. Teilnehmer')->required()->rules(['integer', 'min:1', 'max:999']),
				Field::number('max_participants')->label('max. Teilnehmer')->required()->rules(['integer', 'min:1', 'max:999']),
			])->with(['columns' => 2]),
			// Laptops in the room; legacy's largest is 3.
			Field::number('rentals_available')->label('Verfügbare Mietcomputer')->required()->rules(['integer', 'min:0', 'max:50']),
			// Empty: the course's fee ([[Event::fee]]).
			Field::number('fee')->label('Kosten')->rules(['min:0', 'max:99999.99']),
			Field::row([
				Field::checkbox('online')->label('Onlinekurs'),
				Field::checkbox('free_of_charge')->label('Gratiskurs'),
				Field::checkbox('publish')->label('Publizieren'),
			]),
			Field::select('location', fn () => Location::query()->get()
				->mapWithKeys(fn (Location $location) => [$location->uuid => $location->getTranslation('description', 'de')])
				->all())->label('Ort')->required(),

			/*
			 * One line per course day. Legacy's `min:1` on its text date was a
			 * length check, so a two-digit year went through and hid 14 events
			 * from the site in 2025 ([[02-courses-events]]); a day is `Y-m-d`
			 * here or it fails.
			 */
			Field::repeater('dates', [
				Field::date('date')->label('Datum')->required()
					->message('required', 'Bitte ein Datum erfassen.')
					->message('date_format', 'Bitte als TT.MM.JJJJ erfassen, mit vierstelligem Jahr.'),
				Field::time('time_start')->label('von')
					->message('date_format', 'Bitte als hh.mm erfassen.'),
				Field::time('time_end')->label('bis')->rules(['after:dates.*.time_start'])
					->message('date_format', 'Bitte als hh.mm erfassen.')
					->message('after', 'Endet vor dem Beginn.'),
			], ['date' => '', 'time_start' => '', 'time_end' => ''])
				->label('Veranstaltungsdaten')->required()->rules(['min:1'])
				->message('required', 'Bitte mindestens ein Datum erfassen.')
				->message('min', 'Bitte mindestens ein Datum erfassen.')
				->with(['inline' => true]),

			// The Expert role, not just anyone: several admins do not teach.
			Field::checkboxes('experts', fn () => User::query()
				->whereIn('id', fn ($query) => $query->select('user_id')->from('role_user')->where('role', Role::Expert->value))
				->get()
				->sortBy(fn (User $user) => "{$user->first_name} {$user->last_name}", SORT_NATURAL | SORT_FLAG_CASE)
				->mapWithKeys(fn (User $user) => [$user->uuid => trim("{$user->first_name} {$user->last_name}")])
				->all())->label('Experten')->required()->with(['columns' => 2, 'strong' => true])
				->message('required', 'Bitte mindestens einen Experten wählen.')
				->message('min', 'Bitte mindestens einen Experten wählen.'),
		];
	}

	/**
	 * Legacy's starting values: no laptops, not published, the first location
	 * (there is one). Legacy began with no day at all; one empty line saves
	 * the first click on *+*.
	 */
	public function defaults(): array
	{
		return [
			'registration_until' => '',
			'min_participants' => '',
			'max_participants' => '',
			'rentals_available' => 0,
			'fee' => '',
			'online' => false,
			'free_of_charge' => false,
			'publish' => false,
			'location' => Location::query()->orderBy('id')->value('uuid') ?? '',
			'dates' => [['date' => '', 'time_start' => '', 'time_end' => '']],
			'experts' => [],
		];
	}
}
