<?php

declare(strict_types=1);

namespace App\Forms;

use App\Models\Course;

/**
 * *Vorhaben erfassen* / *bearbeiten* ([[04-content]]): what the review left of
 * the Räume mockup, a title and a text (marker 3), the tile's line for the
 * homepage, and the courses the page lists.
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
			Field::row([
				Field::checkbox('publish')->label('Publizieren'),
			]),
		];
	}

	public function defaults(): array
	{
		return ['title' => '', 'teaser' => '', 'lead' => '', 'text' => '', 'courses' => [], 'publish' => false];
	}
}
