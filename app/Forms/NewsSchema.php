<?php

declare(strict_types=1);

namespace App\Forms;

use App\Support\NewsLink;

/**
 * *News erfassen* / *bearbeiten* ([[News]]): what the homepage's news box
 * shows. A title, a line of text, a page to point at, *Publizieren*, and the
 * picture (its first image is the *Vorschau*). One field per row.
 */
final class NewsSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::text('title')->label('Titel')->required()->rules(['max:120'])
				->with(['hint' => 'Kurz, zum Beispiel «Rhino 9 jetzt verfügbar».']),
			Field::textarea('text')->label('Text')->rules(['max:300'])->with(['rows' => 2]),
			Field::select('link', fn () => NewsLink::options())->label('Link')
				->with(['placeholder' => 'Kein Link', 'hint' => 'Wohin der Pfeil führt. Ist die Seite nicht auf der Website, erscheint die News ohne Pfeil.']),
			// `spaced`: a field's space below it, with no hint to bring it (Marcel, 2026-11-17).
			Field::checkbox('publish')->label('Publizieren')->with(['spaced' => true]),

			Field::section('Bild', [Field::custom('images')->with(['owner' => 'news'])]),
		];
	}

	public function defaults(): array
	{
		return ['title' => '', 'text' => '', 'link' => '', 'publish' => false];
	}
}
