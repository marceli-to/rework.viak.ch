<?php

declare(strict_types=1);

namespace App\Forms;

/**
 * *Testimonial erfassen* / *bearbeiten* ([[07-dashboard]], step 4): what the
 * mockups show of one, and the one placement decided so far.
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
				Field::checkbox('featured')->label('Auf der Startseite'),
			]),
		];
	}

	public function defaults(): array
	{
		return ['quote' => '', 'name' => '', 'context' => '', 'featured' => false, 'publish' => false];
	}
}
