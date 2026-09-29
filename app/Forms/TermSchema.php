<?php

declare(strict_types=1);

namespace App\Forms;

/**
 * *Kategorie*, *Sprache*, *Level*, *Tag* — one form for the four, as
 * `07-dashboard.md` planned: each is a translatable title and nothing else
 * (legacy's four `Form.vue` files differ only in their noun).
 *
 * German only, as the course form: legacy's *Beschreibung (en)* is not asked
 * for, and an English title already stored stays where it is (#6).
 */
final class TermSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::text('title')->label('Bezeichnung')->required(),
		];
	}

	public function defaults(): array
	{
		return ['title' => ''];
	}
}
