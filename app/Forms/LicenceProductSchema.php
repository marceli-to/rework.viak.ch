<?php

declare(strict_types=1);

namespace App\Forms;

use App\Enums\LicenceAccess;
use App\Enums\LicenceType;
use App\Enums\Platform;
use App\Models\Manufacturer;
use App\Models\Software;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * *Lizenz erfassen* / *bearbeiten* ([[05-licences]]): a product and its
 * dropdown. The client creates, edits and deletes these themselves.
 *
 * Each variant is a row of the client's list: article number, **net** price,
 * licence type, named or floating, platforms, a remark, a minimum quantity,
 * and whether it is on the site at all. A demo is a variant at 0 (#35); an
 * EDU licence is one with *Auf der Website* unticked (#36).
 */
final class LicenceProductSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::row([
				Field::select('software', fn () => Software::query()->get()
					->sortBy(fn (Software $software) => $software->getTranslation('title', 'de'), SORT_NATURAL | SORT_FLAG_CASE)
					->mapWithKeys(fn (Software $software) => [$software->uuid => $software->getTranslation('title', 'de')])->all())
					->label('Software')->required()->with(['placeholder' => 'Bitte wählen']),
				Field::select('manufacturer', fn () => Manufacturer::query()->get()
					->sortBy(fn (Manufacturer $maker) => $maker->getTranslation('title', 'de'), SORT_NATURAL | SORT_FLAG_CASE)
					->mapWithKeys(fn (Manufacturer $maker) => [$maker->uuid => $maker->getTranslation('title', 'de')])->all())
					->label('Hersteller')->required()->with(['placeholder' => 'Bitte wählen']),
			])->with(['columns' => 2]),
			Field::text('title')->label('Titel')->required(),
			Field::richtext('description')->label('Beschreibung'),
			// Maxwell V5 and the RealFlow Plugin: one price, the host chosen on the order.
			Field::text('hosts')->label('Hostsoftware')->rules(['max:500'])
				->with(['hint' => 'Kommagetrennt, z.B. Rhino, Archicad, Cinema 4D. Leer lassen, wenn es keine Auswahl gibt.']),
			Field::row([
				Field::checkbox('three_years_on_request')->label('3-Jahreslizenz auf Anfrage'),
				Field::checkbox('publish')->label('Publizieren'),
			]),
			Field::repeater('variants', [
				Field::hidden('uuid'),
				Field::text('title')->label('Variante')->required()
					->message('required', 'Eine Variante braucht eine Bezeichnung.'),
				Field::row([
					Field::text('sku')->label('Artikelnummer')->required()->rules(fn (?Model $product) => [
						'max:32',
						'distinct',
						Rule::unique('licence_variants', 'sku')->whereNull('deleted_at')
							->when($product, fn ($rule) => $rule->whereNot('licence_product_id', $product->getKey())),
					])->message('unique', 'Diese Artikelnummer hat bereits eine andere Lizenz.')
						->message('distinct', 'Diese Artikelnummer steht zweimal in der Liste.'),
					Field::number('price')->label('Preis CHF exkl. MWST')->required()->rules(['min:0', 'max:99999.99', 'decimal:0,2']),
				])->with(['columns' => 2]),
				Field::row([
					Field::select('licence_type', collect(LicenceType::cases())->mapWithKeys(fn (LicenceType $type) => [$type->value => $type->label()])->all())
						->label('Lizenztyp')->with(['placeholder' => 'Keiner (Demo)']),
					Field::select('access', collect(LicenceAccess::cases())->mapWithKeys(fn (LicenceAccess $access) => [$access->value => $access->label()])->all())
						->label('Lizenzzugriff')->with(['placeholder' => 'Bitte wählen']),
				])->with(['columns' => 2]),
				Field::row([
					Field::checkboxes('platforms', fn () => collect(Platform::cases())->mapWithKeys(fn (Platform $platform) => [$platform->value => $platform->label()])->all())
						->label('Plattform')->with(['columns' => 2]),
					Field::number('min_quantity')->label('Mindestmenge')->rules(['integer', 'min:1', 'max:999'])
						->with(['hint' => 'Leer lassen für keine.']),
				])->with(['columns' => 2]),
				Field::text('note')->label('Hinweis')->rules(['max:255'])
					->with(['hint' => 'Steht bei der Variante, z.B. «nur zusammen mit einer Neulizenz».']),
				Field::checkbox('listed')->label('Auf der Website'),
			], [
				'uuid' => null, 'title' => '', 'sku' => '', 'price' => '', 'licence_type' => LicenceType::Subscription->value,
				'access' => LicenceAccess::Named->value, 'platforms' => [Platform::Windows->value, Platform::MacOS->value],
				'note' => '', 'min_quantity' => '', 'listed' => true,
			])->label('Varianten')->required()->with(['add' => 'Variante hinzufügen'])
				->message('required', 'Eine Lizenz braucht mindestens eine Variante.'),
		];
	}

	public function defaults(): array
	{
		return [
			'software' => '', 'manufacturer' => '', 'title' => '', 'description' => '', 'hosts' => '',
			'three_years_on_request' => false, 'publish' => true, 'variants' => [],
		];
	}
}
