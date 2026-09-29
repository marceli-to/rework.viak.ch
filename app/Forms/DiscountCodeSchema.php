<?php

declare(strict_types=1);

namespace App\Forms;

use App\Enums\DiscountType;
use App\Support\DiscountCodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * *Rabatt-Code erfassen* / *bearbeiten* — legacy's `views/discount/Form.vue`
 * ([[07-dashboard]], step 6), in its order.
 *
 * **One field legacy did not have: *Einlösbar*.** Legacy decided how often a
 * code could be used from whether it had dates (none: once; both: unlimited
 * while valid), so the two could never be set apart ([[DiscountCode]]).
 * Chunk 06 made it a column; this makes it a field. A new code starts at
 * once, the safe end.
 */
final class DiscountCodeSchema extends Schema
{
	public function fields(): array
	{
		return [
			// Generated, and shown as legacy shows it: there, not typed.
			Field::text('code')->label('Code')->required()
				->rules(fn (?Model $code) => ['regex:/^VIAK-[A-Z0-9]{4}-[A-Z0-9]{4}$/', Rule::unique('discount_codes', 'code')->ignore($code?->getKey())])
				->with(['readonly' => true]),
			Field::row([
				Field::number('amount')->label('Betrag oder Prozentsatz')->required()->rules(['gt:0', 'max:99999.99']),
				Field::select('type', [
					DiscountType::Fixed->value => 'Fixbetrag (CHF)',
					DiscountType::Percent->value => 'Prozent',
				])->label('Art')->required(),
			])->with(['columns' => 2]),
			Field::row([
				Field::date('valid_from')->label('Gültig ab')
					->message('date_format', 'Bitte als TT.MM.JJJJ erfassen, mit vierstelligem Jahr.'),
				Field::date('valid_to')->label('Gültig bis')->rules(['after_or_equal:valid_from'])
					->message('date_format', 'Bitte als TT.MM.JJJJ erfassen, mit vierstelligem Jahr.')
					->message('after_or_equal', 'Endet vor dem Beginn.'),
			])->with(['columns' => 2]),
			Field::number('usage_limit')->label('Einlösbar (Anzahl Bestellungen)')->rules(['integer', 'min:1', 'max:100000'])
				->with(['hint' => 'Leer lassen für unbegrenzt.']),
			Field::textarea('remarks')->label('Bemerkungen')->rules(['max:255']),
		];
	}

	public function defaults(): array
	{
		return [
			'code' => app(DiscountCodeGenerator::class)->next(),
			'amount' => '',
			'type' => DiscountType::Fixed->value,
			'valid_from' => '',
			'valid_to' => '',
			'usage_limit' => 1,
			'remarks' => '',
		];
	}
}
