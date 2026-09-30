<?php

declare(strict_types=1);

namespace App\Forms;

/**
 * *Rechnung bearbeiten* — legacy's `views/backoffice/invoice/Edit.vue`
 * ([[07-dashboard]], step 7): the invoice address, and nothing else.
 *
 * Legacy's screen showed the number and the student greyed out and one
 * free-text box for the address, while its endpoint took **any** field of
 * the invoice and saved it. Here the endpoint takes the address only, and the
 * address is fields: the QR slip prints the payer from them, and a free text
 * cannot be split back into a street and a town ([[QrBill::debtor]]). The
 * number, date, amount and student are the form's note, not fields.
 *
 * The portal's rule for who is billed: a pair of names or a firm, either alone
 * is enough ([[StoreAddressRequest]], [[CustomerSchema]]).
 */
final class InvoiceSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::row([
				Field::text('first_name')->label('Vorname')->rules(['required_without:company', 'max:255'])
					->message('required_without', 'Bitte Vor- und Nachname oder eine Firma erfassen.'),
				Field::text('last_name')->label('Nachname')->rules(['required_without:company', 'max:255'])
					->message('required_without', 'Bitte Vor- und Nachname oder eine Firma erfassen.'),
			])->with(['columns' => 2]),
			Field::text('company')->label('Firma')->rules(['required_without_all:first_name,last_name', 'max:255'])
				->message('required_without_all', 'Bitte Vor- und Nachname oder eine Firma erfassen.'),
			Field::row([
				Field::text('street')->label('Strasse')->required()->rules(['max:255']),
				Field::text('street_no')->label('Nr.')->rules(['max:15']),
			])->with(['columns' => 2]),
			Field::row([
				Field::text('zip')->label('PLZ')->required()->rules(['max:15']),
				Field::text('city')->label('Ort')->required()->rules(['max:255']),
			])->with(['columns' => 2]),
			Field::select('country', self::countries())->label('Land')->required(),
		];
	}

	/** Never created from the dashboard; the kit asks all the same. */
	public function defaults(): array
	{
		return [
			'first_name' => '', 'last_name' => '', 'company' => '',
			'street' => '', 'street_no' => '', 'zip' => '', 'city' => '', 'country' => 'ch',
		];
	}
}
