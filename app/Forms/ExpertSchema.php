<?php

declare(strict_types=1);

namespace App\Forms;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * *Experte hinzufügen* / *bearbeiten* — legacy's expert form, in its order
 * ([[07-dashboard]], step 6): the person, the two flags the Experten page
 * reads, the roles, *Über* and the portraits.
 *
 * Legacy's required fields are kept: all 20 experts have gender, street, ZIP,
 * city and country (checked 2026-09-29), so no edit is forced to invent one.
 */
final class ExpertSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::select('gender', collect(Gender::cases())->mapWithKeys(fn (Gender $gender) => [$gender->value => $gender->label()])->all())
				->label('Geschlecht')->required()->with(['placeholder' => 'Bitte wählen']),
			Field::text('first_name')->label('Vorname')->required(),
			Field::text('last_name')->label('Name')->required(),
			Field::text('company')->label('Firma'),
			/*
			 * Unique across every account, deleted ones too: the index is, and
			 * legacy let two people share an address without asking.
			 */
			Field::text('email')->label('E-Mail')->required()
				->rules(fn (?Model $user) => ['email', Rule::unique('users', 'email')->ignore($user?->getKey())])
				->message('unique', 'Diese E-Mail-Adresse gehört bereits zu einem Konto.')
				->with(['input' => 'email']),
			Field::text('phone')->label('Telefon')->rules(['max:45']),
			Field::row([
				Field::text('street')->label('Strasse')->required(),
				Field::text('street_no')->label('Nr.')->rules(['max:15']),
			])->with(['columns' => 2]),
			Field::row([
				Field::text('zip')->label('PLZ')->required()->rules(['max:15']),
				Field::text('city')->label('Ort')->required(),
			])->with(['columns' => 2]),
			Field::select('country', fn () => Country::query()->orderBy('order')->orderBy('name')->get()
				->mapWithKeys(fn (Country $country) => [$country->code => $country->getTranslation('name', 'de')])
				->all())->label('Land')->required(),
			Field::row([
				Field::checkbox('subscribe_newsletter')->label('Newsletter abonnieren'),
			]),

			// Both, or the Experten page leaves them out ([[User::scopePubliclyListedExperts]]).
			Field::row([
				Field::checkbox('visible')->label('Experte anzeigen'),
				Field::checkbox('publish')->label('Experte aktiv'),
			]),

			/*
			 * The only place legacy could make anyone an admin, and still the
			 * place for it: roles belong to the person, wherever the person is
			 * edited. Taking the Expert role away takes them off this list.
			 * Legacy's collapsible around three boxes, always open, is a plain
			 * group here; four to a row, as its `span-3`.
			 */
			Field::checkboxes('roles', fn () => [
				Role::Admin->value => 'Admin',
				Role::Expert->value => 'Experte',
				Role::Student->value => 'Student',
			])->label('Benutzer-Rollen')->required()->with(['columns' => 4])
				->message('required', 'Bitte mindestens eine Rolle wählen.')
				->message('min', 'Bitte mindestens eine Rolle wählen.'),

			Field::section('Über', [
				Field::text('title')->label('Titel'),
				Field::richtext('description')->label('Beschreibung'),
			]),

			// The square teaser on the Experten page, the 16:9 visual on their own.
			Field::section('Profilbild', [Field::custom('images')->with(['owner' => 'experts'])]),
		];
	}

	/** Legacy's starting values: the Expert role, both flags off, no newsletter. */
	public function defaults(): array
	{
		return [
			'gender' => '',
			'first_name' => '', 'last_name' => '', 'company' => '', 'email' => '', 'phone' => '',
			'street' => '', 'street_no' => '', 'zip' => '', 'city' => '', 'country' => 'ch',
			'subscribe_newsletter' => false,
			'visible' => false,
			'publish' => false,
			'roles' => [Role::Expert->value],
			'title' => '',
			'description' => '',
		];
	}
}
