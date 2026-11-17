<?php

declare(strict_types=1);

namespace App\Forms;

/**
 * *Seite «Angebot»* ([[14-angebote]] *The form, by page*): the Angebot page's
 * texts, its pictures and its SEO, a form of its own behind its row in the
 * *Software* list since 2026-11-17 (Marcel). Saved by
 * [[SaveSoftwarePageRequest]], which writes only these fields.
 */
final class SoftwareOfferPageSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::richtext('short_description')->label('Kurzbeschrieb'),
			Field::textarea('audiences')->label('Zielgruppen')->rules(['max:1000'])
				->with(['rows' => 2, 'hint' => 'Eine Zeile, zum Beispiel «Architektur- & Ingenieurbüros, Industrial Design».']),
			Field::textarea('intro')->label('Einleitung')->rules(['max:1000'])
				->with(['rows' => 2, 'hint' => 'Ein Satz, der zu Kurse und Lizenzen überleitet.']),
			Field::textarea('courses_intro')->label('Einleitung Kurse')->rules(['max:1000'])->with(['rows' => 2]),
			Field::textarea('licences_intro')->label('Einleitung Lizenzen')->rules(['max:1000'])->with(['rows' => 2]),
			// Where the licence price would stand, when VIAK does not sell it ([[14-angebote]]).
			Field::text('licence_note')->label('Lizenz-Hinweis')->rules(['max:100'])
				->with(['hint' => 'Nur wenn wir die Lizenz nicht selbst verkaufen: steht statt des Preises, zum Beispiel «Lizenz direkt bei Adobe».']),
			Field::textarea('quote')->label('Zitatbox')->rules(['max:1000'])->with(['rows' => 2]),

			Field::section('Bilder', [Field::custom('images')->with(['owner' => 'software'])]),

			Field::section('Metatags + SEO', [
				Field::textarea('seo_description')->label('SEO - Beschreibung')->rules(['max:1000']),
				Field::textarea('seo_tags')->label('SEO - Keywords')->rules(['max:1000']),
			]),
		];
	}

	public function defaults(): array
	{
		return [
			'short_description' => '', 'audiences' => '', 'intro' => '', 'courses_intro' => '', 'licences_intro' => '',
			'licence_note' => '', 'quote' => '', 'seo_description' => '', 'seo_tags' => '',
		];
	}
}
