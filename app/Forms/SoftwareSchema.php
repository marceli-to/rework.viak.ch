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
 * *Software erfassen* / *bearbeiten* ([[05-licences]]): the record, what it
 * is and what it recommends. Its pages' texts were sections here until
 * 2026-11-17 and are forms of their own now (see below).
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
			 * **The pages are forms of their own** (Marcel, 2026-11-17), each a
			 * row under the software in the *Software* list: [[SoftwareOfferPageSchema]]
			 * (with Bilder and SEO), [[SoftwareAboutPageSchema]],
			 * [[SoftwareLicencesPageSchema]]. Three shut sections here were one
			 * screen doing four jobs. What stays is the record itself.
			 */

			// Both pickers in one section, flat (Marcel, 2026-11-12): two shut
			// sections for one idea was one too many.
			// *Das könnte dich auch interessieren* under the page and in the basket ([[Recommendations]]).
			Field::section('Empfehlungen', array_map(
				fn (Field $picker) => $picker->with(['flat' => true]),
				Recommendations::fields('Erscheinen unter «Das könnte dich auch interessieren» auf der Angebotsseite und im Warenkorb, sobald eine Lizenz dieses Angebots darin liegt.'),
			))->with(['count' => ['recommended_courses', 'recommended_software']]),
		];
	}

	public function defaults(): array
	{
		return [
			'title' => '', 'publish' => false, 'featured' => false,
			'plugin' => false, 'categories' => [], 'hosts' => [],
			'recommended_courses' => [], 'recommended_software' => [],
		];
	}
}
