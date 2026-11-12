<?php

declare(strict_types=1);

namespace App\Forms;

use App\Models\Category;
use App\Models\Software;
use App\Support\Recommendations;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * *Software erfassen* / *bearbeiten* ([[05-licences]]): its page, drawn as a
 * course page is, so the fields are the course form's, in its order and with
 * its labels (Marcel, 2026-10-08). What a course has and a software does not:
 * number, fee, facts, the PDF text, levels, languages, videos, and the
 * second, unlabelled column of *Weitere Informationen* (Marcel, 2026-10-08).
 */
final class SoftwareSchema extends Schema
{
	public function fields(): array
	{
		return [
			// One name per software, whatever its case, as the settings list refuses ([[SettingController]]).
			Field::text('title')->label('Titel')->required()->rules(fn (?Model $software) => [
				'max:255',
				function (string $attribute, mixed $value, Closure $fail) use ($software): void {
					$taken = Software::query()->get()
						->reject(fn (Software $other) => $software?->is($other))
						->contains(fn (Software $other) => Str::lower(trim((string) $other->getTranslation('title', 'de', false))) === Str::lower(trim((string) $value)));

					if ($taken) {
						$fail('Diese Bezeichnung gibt es schon.');
					}
				},
			]),
			/*
			 * **What the record is, first and open** (Marcel, 2026-11-12): it
			 * decides what else the form shows. One field per row. *Läuft in*
			 * only for a plugin (`when`).
			 */
			Field::section('Einstellungen', [
				Field::checkbox('publish')->label('Publizieren')
					->with(['hint' => 'Auf der Website, sobald es einen publizierten Kurs oder eine Lizenz im Shop hat.']),
				// *Beliebte Angebote* on the homepage, as a course's ([[04-content]]).
				Field::checkbox('featured')->label('Beliebt')
					->with(['hint' => 'Unter «Beliebte Angebote» auf der Startseite.']),
				// A plugin is an Angebot of its own (#72, the client 2026-11-12).
				Field::checkbox('plugin')->label('Plugin')
					->with(['hint' => 'Läuft in anderen Programmen, z.B. Bongo, V-Ray. Steht so auf seiner Seite und seinen Kacheln.']),
				// Where a plugin runs: *Plugin für …* on its page, and its products
				// under *Plugins* on each host's.
				// Programs only: a plugin does not run in a plugin (the list was all 49 records).
				Field::checkboxes('hosts', fn () => Software::query()->where('plugin', false)->get()
					->mapWithKeys(fn (Software $software) => [$software->uuid => $software->getTranslation('title', 'de')])
					->sortBy(fn (string $label) => $label, SORT_NATURAL | SORT_FLAG_CASE)
					->all())->label('Läuft in')
					->with(['columns' => 2, 'when' => 'plugin', 'hint' => 'Nur wo es an bestimmte Programme gebunden ist, z.B. Bongo an Rhinoceros. Ein Plugin für viele Programme (V-Ray): keines ankreuzen.']),
				Field::checkboxes('categories', Category::class)->label('Kategorien'),
			])->with(['open' => true]),

			/*
			 * **One section per page**, each with that page's texts in its order
			 * and a link to the page as saved (`preview`) (Marcel, 2026-11-09,
			 * [[14-angebote]] *The form, by page*). Only the title stands above:
			 * it names the record everywhere, not one page.
			 * *Subtitel* and *Weitere Informationen* left the form: the client's
			 * texts have neither. Their columns stay, and the page still shows
			 * what they hold.
			 */
			Field::section('Seite «Angebot»', [
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
			])->with(['preview' => 'url', 'filled' => true]),

			// Its own page since #70 (the client, 2026-11-06), linked from the Angebot's.
			Field::section('Seite «Was ist …?»', [
				Field::richtext('full_description')
					->with(['hint' => 'Zwischentitel wie «Woher kommt …?» fett, als eigene Zeile. Leer: keine Seite und kein Link.']),
			])->with(['preview' => 'about_url', 'filled' => true]),

			// Its licence page since #71 (the client, 2026-11-12): one page per
			// Angebot, so only for one with licence products (`whenMeta`).
			Field::section('Seite «Lizenzen»', [
				Field::textarea('licences_lead')->label('Einleitung')->rules(['max:1000'])
					->with(['rows' => 2, 'hint' => 'Unter dem Titel «… Lizenzen», z.B. «Corona Lizenzen für Einzelpersonen und Teams».']),
				Field::richtext('requirements')->label('Systemvoraussetzungen')
					->with(['hint' => 'Einmal für alle Lizenzen dieses Angebots.']),
				Field::textarea('licences_quote')->label('Zitatbox')->rules(['max:1000'])->with(['rows' => 2]),
			])->with(['preview' => 'licences_url', 'filled' => true, 'whenMeta' => 'products_count']),

			// Both pickers in one section, flat (Marcel, 2026-11-12): two shut
			// sections for one idea was one too many.
			// *Das könnte dich auch interessieren* under the page and in the basket ([[Recommendations]]).
			Field::section('Empfehlungen', array_map(
				fn (Field $picker) => $picker->with(['flat' => true]),
				Recommendations::fields('Erscheinen unter «Das könnte dich auch interessieren» auf der Angebotsseite und im Warenkorb, sobald eine Lizenz dieses Angebots darin liegt.'),
			))->with(['count' => ['recommended_courses', 'recommended_software']]),

			Field::section('Bilder', [Field::custom('images')->with(['owner' => 'software'])]),

			Field::section('Metatags + SEO', [
				Field::textarea('seo_description')->label('SEO - Beschreibung')->rules(['max:1000']),
				Field::textarea('seo_tags')->label('SEO - Keywords')->rules(['max:1000']),
			])->with(['filled' => true]),
		];
	}

	public function defaults(): array
	{
		return [
			'title' => '', 'publish' => false, 'featured' => false,
			'short_description' => '', 'full_description' => '', 'licence_note' => '',
			'audiences' => '', 'intro' => '', 'courses_intro' => '', 'licences_intro' => '', 'quote' => '',
			'licences_lead' => '', 'requirements' => '', 'licences_quote' => '',
			'plugin' => false, 'categories' => [], 'hosts' => [],
			'recommended_courses' => [], 'recommended_software' => [],
			'seo_description' => '', 'seo_tags' => '',
		];
	}
}
