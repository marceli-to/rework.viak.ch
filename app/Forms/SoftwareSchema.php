<?php

declare(strict_types=1);

namespace App\Forms;

use App\Models\Category;
use App\Models\Software;
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
			Field::textarea('subtitle')->label('Subtitel')->rules(['max:1000']),
			Field::richtext('short_description')->label('Kurzbeschrieb'),
			Field::richtext('full_description')->label('Detailbeschrieb'),
			Field::richtext('information')->label('Weitere Informationen'),

			Field::section('Einstellungen', [
				Field::row([
					Field::checkbox('publish')->label('Publizieren'),
					// *Beliebte Angebote* on the homepage, as a course's ([[04-content]]).
					Field::checkbox('featured')->label('Beliebt'),
				]),
				Field::checkboxes('categories', Category::class)->label('Kategorien'),
			])->with(['open' => true]),

			// Saved on their own, each action at once ([[ImageSection]]).
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
			'title' => '', 'subtitle' => '', 'publish' => false, 'featured' => false,
			'short_description' => '', 'full_description' => '', 'information' => '',
			'categories' => [],
			'seo_description' => '', 'seo_tags' => '',
		];
	}
}
