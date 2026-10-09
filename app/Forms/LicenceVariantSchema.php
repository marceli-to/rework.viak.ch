<?php

declare(strict_types=1);

namespace App\Forms;

use App\Enums\LicenceAccess;
use App\Enums\LicenceType;
use App\Enums\Platform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * *Lizenz erfassen* / *bearbeiten* ([[05-licences]]), a variant in the code: one entry of a
 * product's dropdown, and one row of the client's list. Article number,
 * **net** price, licence type, *Nutzung* (Einzelplatz or Netzwerk), platforms, a minimum
 * quantity, a remark, and whether it is on the site at all. A demo is a
 * variant at 0 (#35); an EDU licence one with *Im Shop bestellbar* unticked (#36).
 */
final class LicenceVariantSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::text('title')->label('Titel')->required(),
			Field::row([
				Field::text('sku')->label('Artikelnummer')->required()->rules(fn (?Model $variant) => [
					'max:32',
					Rule::unique('licence_variants', 'sku')->whereNull('deleted_at')->ignore($variant?->getKey()),
				])->message('unique', 'Diese Artikelnummer hat bereits eine andere Lizenz.'),
				Field::number('price')->label('Preis CHF exkl. '.config('invoice.vat_label'))->required()->rules(['min:0', 'max:99999.99', 'decimal:0,2'])
					->with(['hint' => '0 für eine Demoversion.']),
			])->with(['columns' => 2]),
			Field::row([
				Field::select('licence_type', collect(LicenceType::cases())->mapWithKeys(fn (LicenceType $type) => [$type->value => $type->label()])->all())
					->label('Lizenztyp')->with(['placeholder' => 'Keiner (Demo)']),
				Field::select('access', collect(LicenceAccess::cases())->mapWithKeys(fn (LicenceAccess $access) => [$access->value => $access->label()])->all())
					->label('Nutzung')->with(['placeholder' => 'Bitte wählen']),
			])->with(['columns' => 2]),
			Field::checkboxes('platforms', fn () => collect(Platform::cases())->mapWithKeys(fn (Platform $platform) => [$platform->value => $platform->label()])->all())
				->label('Plattform')->with(['columns' => 4]),
			Field::row([
				Field::number('min_quantity')->label('Mindestmenge')->rules(['integer', 'min:1', 'max:999'])
					->with(['hint' => 'Kleinste Stückzahl im Warenkorb, z.B. 3 bei Teams-Lizenzen. Leer: ab 1 Stück.']),
				Field::text('note')->label('Hinweis')->rules(['max:255'])
					->with(['hint' => 'z.B. «nur zusammen mit einer Neulizenz».']),
			])->with(['columns' => 2]),
			Field::row([
				// Not *Publizieren*: that is the product's. Unticked is an EDU or
				// lab licence, picked only when VIAK enters an order (#36).
				Field::checkbox('listed')->label('Im Shop bestellbar')
					->with(['hint' => 'Ohne Haken nicht im Shop, nur für Bestellungen, die ihr selbst erfasst (z.B. EDU-Lizenzen).']),
			]),
		];
	}

	public function defaults(): array
	{
		return [
			'title' => '', 'sku' => '', 'price' => '', 'licence_type' => LicenceType::Subscription->value,
			'access' => LicenceAccess::Named->value, 'platforms' => [Platform::Windows->value, Platform::MacOS->value],
			'min_quantity' => '', 'note' => '', 'listed' => true,
		];
	}
}
