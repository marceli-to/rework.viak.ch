<?php

declare(strict_types=1);

namespace App\Forms;

/**
 * *Seite «Was ist …?»* (#70, [[14-angebote]]): one text, its own page,
 * linked from the Angebot's. A form of its own since 2026-11-17 (Marcel).
 */
final class SoftwareAboutPageSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::richtext('full_description')->label('Text')
				->with(['headings' => true, 'hint' => 'Leer: keine Seite und kein Link.']),
		];
	}

	public function defaults(): array
	{
		return ['full_description' => ''];
	}
}
