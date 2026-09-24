<?php

declare(strict_types=1);

namespace App\Forms;

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
      Field::row([
				Field::checkbox('publish')->label('Publizieren'),
			]),
		];
	}

	public function defaults(): array
	{
		return ['quote' => '', 'name' => '', 'context' => '', 'publish' => false];
	}
}
