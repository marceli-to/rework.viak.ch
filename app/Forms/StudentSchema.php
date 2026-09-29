<?php

declare(strict_types=1);

namespace App\Forms;

use App\Enums\Role;

/**
 * *Student hinzufügen* / *bearbeiten* — legacy's `views/student/Form.vue` and
 * its address sub-form ([[07-dashboard]], step 6).
 *
 * What differs from legacy:
 *
 * - **Company** is on it. Legacy's admin form left it out, but 259 students
 *   have one (the checkout asks), so an admin could see it and never fix it.
 * - **E-mail** is on both create and edit. Legacy asked for it only on create,
 *   with a password the admin typed; the password is gone (the student sets
 *   their own through the invite, #26, chunk 10).
 * - **Roles**, as on the expert form: they belong to the person.
 * - **Invoice addresses are rows of this form**, not two screens of their own,
 *   saved with it. A removed one is soft-deleted, as the portal does.
 *
 * Legacy's required fields are kept, phone included. Five of the 570 students
 * miss one of them (checked 2026-09-29), and their first edit asks for it.
 */
final class StudentSchema extends Schema
{
	public function fields(): array
	{
		return [
			self::genderField(),
			Field::text('first_name')->label('Vorname')->required(),
			Field::text('last_name')->label('Name')->required(),
			Field::text('company')->label('Firma'),
			self::emailField(),
			Field::text('phone')->label('Telefon')->required()->rules(['max:45']),
			Field::row([
				Field::text('street')->label('Strasse')->required(),
				Field::text('street_no')->label('Nr.')->rules(['max:15']),
			])->with(['columns' => 2]),
			Field::row([
				Field::text('zip')->label('PLZ')->required()->rules(['max:15']),
				Field::text('city')->label('Ort')->required(),
			])->with(['columns' => 2]),
			Field::select('country', self::countries())->label('Land')->required(),
			Field::row([
				Field::checkbox('subscribe_newsletter')->label('Newsletter abonnieren'),
			]),

			self::roleField(),

			/*
			 * The portal's rule, not legacy's: a pair of names or a firm, either
			 * alone is enough — *Rechnungen, Muster AG* needs no contact person
			 * ([[StoreAddressRequest]]).
			 */
			Field::section('Rechnungsadressen', [
				Field::repeater('addresses', [
					Field::hidden('uuid'),
					Field::row([
						Field::text('first_name')->label('Vorname')->rules(['required_without:addresses.*.company'])
							->message('required_without', 'Bitte Vor- und Nachname oder eine Firma erfassen.'),
						Field::text('last_name')->label('Nachname')->rules(['required_without:addresses.*.company'])
							->message('required_without', 'Bitte Vor- und Nachname oder eine Firma erfassen.'),
					])->with(['columns' => 2]),
					Field::text('company')->label('Firma')->rules(['required_without_all:addresses.*.first_name,addresses.*.last_name'])
						->message('required_without_all', 'Bitte Vor- und Nachname oder eine Firma erfassen.'),
					Field::row([
						Field::text('street')->label('Strasse')->required(),
						Field::text('street_no')->label('Nr.')->rules(['max:15']),
					])->with(['columns' => 2]),
					Field::row([
						Field::text('zip')->label('PLZ')->required()->rules(['max:15']),
						Field::text('city')->label('Ort')->required(),
					])->with(['columns' => 2]),
					Field::select('country', self::countries())->label('Land')->required(),
				], [
					'uuid' => null, 'first_name' => '', 'last_name' => '', 'company' => '',
					'street' => '', 'street_no' => '', 'zip' => '', 'city' => '', 'country' => 'ch',
				])->with(['add' => 'Adresse hinzufügen']),
			]),
		];
	}

	/** Legacy's starting values: the Student role, Switzerland, no newsletter. */
	public function defaults(): array
	{
		return [
			'gender' => '',
			'first_name' => '', 'last_name' => '', 'company' => '', 'email' => '', 'phone' => '',
			'street' => '', 'street_no' => '', 'zip' => '', 'city' => '', 'country' => 'ch',
			'subscribe_newsletter' => false,
			'roles' => [Role::Student->value],
			'addresses' => [],
		];
	}
}
