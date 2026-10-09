<?php

declare(strict_types=1);

namespace App\Forms;

use App\Models\Course;
use App\Models\Software;

/**
 * *Vorhaben erfassen* / *bearbeiten* ([[04-content]]): what the review left of
 * the Räume mockup, a title and a text (marker 3), the tile's line for the
 * homepage, and the courses and software the page lists.
 */
final class ProjectSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::text('title')->label('Titel')->required(),
			// The tile's second line under *Was möchtest du machen?*.
			Field::text('teaser')->label('Kurzbeschrieb (Startseite)'),
			Field::textarea('lead')->label('Einleitung')->rules(['max:500'])->with(['rows' => 2]),
			Field::richtext('text')->label('Text'),
			Field::offers('courses', fn () => Course::query()->orderBy('number')->get()
				->mapWithKeys(fn (Course $course) => [$course->uuid => [
					'label' => $course->getTranslation('title', 'de'),
					'hint' => $course->displayNumber(),
					'publish' => $course->publish,
				]])->all())->label('Kurse'),
			// Listed after the courses ([[04-content]]); a software the shop does not sell stays off the page.
			Field::offers('software', fn () => Software::query()->ordered()->get()
				->mapWithKeys(fn (Software $software) => [$software->uuid => [
					'label' => $software->getTranslation('title', 'de'),
					'publish' => $software->publish,
				]])->all())->label('Software'),
			Field::row([
				Field::checkbox('publish')->label('Publizieren'),
			]),
			// As the course form has them; the page falls back to the lead.
			Field::section('Metatags + SEO', [
				Field::textarea('seo_description')->label('SEO - Beschreibung')->rules(['max:1000']),
				Field::textarea('seo_tags')->label('SEO - Keywords')->rules(['max:1000']),
			]),
		];
	}

	public function defaults(): array
	{
		return ['title' => '', 'teaser' => '', 'lead' => '', 'text' => '', 'courses' => [], 'software' => [], 'publish' => false, 'seo_description' => '', 'seo_tags' => ''];
	}
}
