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
			Field::text('hosts')->label('Hostsoftware')->rules(['max:500'])
				->with(['hint' => 'Kommagetrennt, z.B. Rhino, Archicad, Cinema 4D. Leer lassen, wenn es keine Auswahl gibt.']),
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
			'software' => '', 'manufacturer' => '', 'title' => '', 'description' => '', 'hosts' => '',
			'three_years_on_request' => false, 'publish' => true,
		];
	}
}
