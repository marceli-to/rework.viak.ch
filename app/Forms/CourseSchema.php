<?php

declare(strict_types=1);

namespace App\Forms;

use App\Models\Category;
use App\Models\Language;
use App\Models\Level;
use App\Models\Software;
use App\Models\Tag;
use App\Support\CourseNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * *Kurs erfassen* / *bearbeiten* — legacy's course form, in its order
 * ([[07-dashboard]], step 2).
 */
final class CourseSchema extends Schema
{
	public function fields(): array
	{
		return [
			/*
			 * Typed, as in legacy, and never reused: numbers reach invoices
			 * through `Event::number()`, so `unique` checks soft-deleted
			 * courses too ([[CourseNumber]]).
			 */
			Field::number('number')->label('Nummer')->required()
				->rules(fn (?Model $course) => ['integer', 'min:1', 'max:65535', Rule::unique('courses', 'number')->ignore($course?->getKey())])
				->message('unique', 'Diese Nummer ist bereits vergeben, auch gelöschte Kurse behalten ihre.'),
			Field::text('title')->label('Titel')->required(),
			Field::textarea('subtitle')->label('Subtitel')->required()->rules(['max:1000']),
			Field::number('fee')->label('Kosten')->required()->rules(['min:0', 'max:99999.99']),
			Field::richtext('short_description')->label('Kurzbeschrieb')->required(),
			Field::richtext('full_description')->label('Detailbeschrieb'),
			Field::richtext('information_booking')->label('Weitere Informationen'),
			Field::richtext('information_content'),
			Field::richtext('summary')->label('Kursbeschreibung (PDF)'),

			Field::section('Facts', [
				Field::richtext('facts.0'),
				Field::richtext('facts.1'),
				Field::richtext('facts.2'),
			]),

			Field::section('Einstellungen', [
				Field::row([
					Field::checkbox('online')->label('Onlinekurs'),
					Field::checkbox('publish')->label('Publizieren'),
				]),
				Field::checkboxes('categories', Category::class)->label('Kategorien')->required()
					->message('required', 'Bitte mindestens eine Kategorie wählen.')->message('min', 'Bitte mindestens eine Kategorie wählen.'),
				Field::checkboxes('languages', Language::class)->label('Sprachen')->required()
					->message('required', 'Bitte mindestens eine Sprache wählen.')->message('min', 'Bitte mindestens eine Sprache wählen.'),
				Field::checkboxes('levels', Level::class)->label('Levels')->required()
					->message('required', 'Bitte mindestens ein Level wählen.')->message('min', 'Bitte mindestens ein Level wählen.'),
				Field::checkboxes('software', Software::class)->label('Software'),
				Field::checkboxes('tags', Tag::class)->label('Tags')->with(['columns' => 2]),
			])->with(['open' => true]),

			// Saved on their own, each action at once ([[ImageSection]]).
			Field::section('Bilder', [Field::custom('images')]),

			Field::section('Videos', [
				Field::repeater('videos', [
					Field::hidden('uuid'),
					Field::text('title')->label('Titel'),
					Field::textarea('code')->label('Code')->required()->with(['mono' => true, 'rows' => 3])
						->message('required', 'Ein Video braucht einen Code.'),
					Field::checkbox('publish')->label('Publizieren'),
				], ['uuid' => null, 'title' => '', 'code' => '', 'publish' => true])->with(['add' => 'Video hinzufügen']),
			]),

			Field::section('Metatags + SEO', [
				Field::textarea('seo_description')->label('SEO - Beschreibung')->rules(['max:1000']),
				Field::textarea('seo_tags')->label('SEO - Keywords')->rules(['max:1000']),
			]),
		];
	}

	public function defaults(): array
	{
		return [
			'number' => app(CourseNumber::class)->next(),
			'title' => '', 'subtitle' => '', 'fee' => '', 'online' => false, 'publish' => false,
			'short_description' => '', 'full_description' => '', 'information_booking' => '', 'information_content' => '', 'summary' => '',
			'facts' => ['', '', ''],
			'categories' => [], 'languages' => [], 'levels' => [], 'software' => [], 'tags' => [],
			'videos' => [],
			'seo_description' => '', 'seo_tags' => '',
		];
	}
}
