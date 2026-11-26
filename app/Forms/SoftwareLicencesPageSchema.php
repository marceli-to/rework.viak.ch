<?php

declare(strict_types=1);

namespace App\Forms;

/**
 * *Seite «Lizenzen»* (#71, [[14-angebote]]): the Angebot's licence page. A
 * form of its own since 2026-11-17 (Marcel); its row in the *Software* list
 * shows only while the software has licence products.
 */
final class SoftwareLicencesPageSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::textarea('licences_lead')->label('Einleitung')->rules(['max:1000'])
				->with(['rows' => 2, 'hint' => 'Unter dem Titel «… Lizenzen», z.B. «Corona Lizenzen für Einzelpersonen und Teams».']),
			Field::richtext('requirements')->label('Systemvoraussetzungen')
				->with(['headings' => true, 'hint' => 'Einmal für alle Lizenzen dieses Angebots.']),
			Field::textarea('licences_quote')->label('Zitatbox')->rules(['max:1000'])->with(['rows' => 2]),
		];
	}

	public function defaults(): array
	{
		return ['licences_lead' => '', 'requirements' => '', 'licences_quote' => ''];
	}
}
