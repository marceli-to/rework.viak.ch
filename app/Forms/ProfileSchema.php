<?php

declare(strict_types=1);

namespace App\Forms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * *Mein Profil* on the dashboard — legacy's `views/admin/Index.vue`
 * ([[07-dashboard]], step 6): the admin's own details, then *Zugangsdaten*.
 *
 * Legacy's required fields: gender, names, country. Changing the address or
 * the password asks for the current password, and a new address has to be
 * confirmed again — [[UpdateProfile]]'s rules, the portal's, not legacy's,
 * which asked for neither ([[SaveProfileRequest]]).
 */
final class ProfileSchema extends Schema
{
	public function fields(): array
	{
		return [
			self::genderField(),
			Field::text('first_name')->label('Vorname')->required(),
			Field::text('last_name')->label('Nachname')->required(),
			Field::text('company')->label('Firma'),
			Field::text('phone')->label('Telefon')->rules(['max:45']),
			Field::row([
				Field::text('street')->label('Strasse'),
				Field::text('street_no')->label('Nr.')->rules(['max:15']),
			])->with(['columns' => 2]),
			Field::row([
				Field::text('zip')->label('PLZ')->rules(['max:15']),
				Field::text('city')->label('Ort'),
			])->with(['columns' => 2]),
			Field::select('country', self::countries())->label('Land')->required(),

			Field::section('Zugangsdaten', [
				Field::text('email')->label('E-Mail')->required()
					->rules(fn (?Model $user) => ['email', Rule::unique('users', 'email')->ignore($user?->getKey())])
					->message('unique', 'Diese E-Mail-Adresse gehört bereits zu einem Konto.')
					->with(['input' => 'email', 'hint' => 'Eine neue Adresse muss bestätigt werden.']),
				Field::text('password')->label('Neues Passwort')->rules(['min:8', 'confirmed'])
					->message('confirmed', 'Die beiden Passwörter stimmen nicht überein.')
					->with(['input' => 'password', 'hint' => 'Mindestens 8 Zeichen. Leer lassen, um es zu behalten.']),
				Field::text('password_confirmation')->label('Neues Passwort wiederholen')->with(['input' => 'password']),
				Field::text('current_password')->label('Aktuelles Passwort')
					->with(['input' => 'password', 'hint' => 'Nur nötig, wenn du E-Mail oder Passwort änderst.']),
			]),
		];
	}

	public function defaults(): array
	{
		return [
			'gender' => '', 'first_name' => '', 'last_name' => '', 'company' => '', 'phone' => '',
			'street' => '', 'street_no' => '', 'zip' => '', 'city' => '', 'country' => 'ch',
			'email' => '', 'password' => '', 'password_confirmation' => '', 'current_password' => '',
		];
	}
}
