<?php

declare(strict_types=1);

namespace App\Forms;

/**
 * *Firma* ([[16-firmenkurse]]): a company a Firmenkurs is held for, as the
 * invoice address it is billed to. [[InvoiceSchema]]'s fields, the company
 * first and required, since that is what it is.
 */
final class CompanySchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::text('company')->label('Firma')->required(),
			Field::row([
				Field::text('first_name')->label('Vorname'),
				Field::text('last_name')->label('Nachname'),
			])->with(['columns' => 2]),
			Field::row([
				Field::text('street')->label('Strasse')->required(),
				Field::text('street_no')->label('Nr.')->rules(['max:15']),
			])->with(['columns' => 2]),
			Field::row([
				Field::text('zip')->label('PLZ')->required()->rules(['max:15']),
				Field::text('city')->label('Ort')->required(),
			])->with(['columns' => 2]),
			Field::select('country', self::countries())->label('Land')->required(),
		];
	}

	public function defaults(): array
	{
		return [
			'company' => '', 'first_name' => '', 'last_name' => '',
			'street' => '', 'street_no' => '', 'zip' => '', 'city' => '', 'country' => 'ch',
		];
	}
}
