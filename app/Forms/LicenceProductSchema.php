<?php

declare(strict_types=1);

namespace App\Forms;

use App\Models\Manufacturer;
use App\Models\Software;

/**
 * *Produkt erfassen* / *bearbeiten* ([[05-licences]]). The client
 * creates, edits and deletes these themselves.
 *
 * Its licences (variants) are a list under the fields, each with a form of its own
 * ([[LicenceVariantSchema]]), as Marcel asked on 2026-10-08: a repeater put
 * every variant's nine fields on one page.
 */
final class LicenceProductSchema extends Schema
{
	public function fields(): array
	{
		return [
			// Title first, then where it sits, each on its own line (Marcel, 2026-10-08).
			// `create`: a `+` beside the label adds a software or a maker in a lightbox.
			Field::text('title')->label('Titel')->required(),
			Field::select('software', fn () => Software::query()->get()
				->sortBy(fn (Software $software) => $software->getTranslation('title', 'de'), SORT_NATURAL | SORT_FLAG_CASE)
				->mapWithKeys(fn (Software $software) => [$software->uuid => $software->getTranslation('title', 'de')])->all())
				->label('Software')->required()->with(['placeholder' => 'Bitte wählen', 'create' => 'software']),
			Field::select('manufacturer', fn () => Manufacturer::query()->get()
				->sortBy(fn (Manufacturer $maker) => $maker->getTranslation('title', 'de'), SORT_NATURAL | SORT_FLAG_CASE)
				->mapWithKeys(fn (Manufacturer $maker) => [$maker->uuid => $maker->getTranslation('title', 'de')])->all())
				->label('Hersteller')->required()->with(['placeholder' => 'Bitte wählen', 'create' => 'manufacturers']),
			Field::richtext('description')->label('Beschreibung'),
			// Maxwell V5 and the RealFlow Plugin: one price, the host chosen on the order.
			// Picked from the Software list, so a program has one spelling (Marcel, 2026-10-09).
			// In a collapsible, shut: two products of 39 have any (Marcel, 2026-10-09).
			Field::section('Hostsoftware', [
				Field::checkboxes('hosts', Software::class)->label('Hostsoftware')
					->with(['columns' => 2, 'legend' => false, 'hint' => 'Nur für Plugins: die Programme, in denen das Plugin läuft. Bei der Bestellung muss eines davon gewählt werden. Sonst keines ankreuzen.']),
			])->with(['count' => 'hosts']),
			Field::row([
				Field::checkbox('three_years_on_request')->label('3-Jahreslizenz auf Anfrage'),
				Field::checkbox('publish')->label('Publizieren'),
			]),
			// Saved on their own, each in its own form ([[LicenceVariantSchema]]).
			Field::custom('variants'),
		];
	}

	public function defaults(): array
	{
		return [
			'software' => '', 'manufacturer' => '', 'title' => '', 'description' => '', 'hosts' => [],
			'three_years_on_request' => false, 'publish' => true,
		];
	}
}
