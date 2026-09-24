<?php

declare(strict_types=1);

namespace App\Forms;

use App\Models\Course;
use App\Models\Software;

/**
 * *Testimonial erfassen* / *bearbeiten* ([[07-dashboard]], step 4): what the
 * mockups show of one. **Not where it appears** — the page that shows
 * testimonials picks them, with a picker on the course form and the homepage
 * form (Marcel, 2026-09-24).
 */
final class TestimonialSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::textarea('quote')->label('Zitat')->required()->rules(['max:1000'])->with(['rows' => 3]),
      Field::text('name')->label('Name')->required(),
			Field::text('context')->label('Firma, Ort'),
			/*
			 * What it is about — the hint a page's picker shows beside it, and
			 * what it sorts by. Not where it stands: the pickers decide that.
			 */
			Field::select('subject', fn () => [
				'Kurse' => Course::query()->orderBy('number')->get()
					->mapWithKeys(fn (Course $course) => ['course:'.$course->uuid => $course->number.' '.$course->getTranslation('title', 'de')])->all(),
				'Software' => Software::query()->get()
					->sortBy(fn (Software $software) => $software->getTranslation('title', 'de'), SORT_NATURAL | SORT_FLAG_CASE)
					->mapWithKeys(fn (Software $software) => ['software:'.$software->uuid => $software->getTranslation('title', 'de')])->all(),
			])->label('Bezieht sich auf')->with(['placeholder' => 'Allgemein — die VIAK als Ganzes']),
      Field::row([
				Field::checkbox('publish')->label('Publizieren'),
			]),
		];
	}

	public function defaults(): array
	{
		return ['quote' => '', 'name' => '', 'context' => '', 'subject' => '', 'publish' => false];
	}
}
