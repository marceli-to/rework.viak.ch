<?php

declare(strict_types=1);

namespace App\Forms;

/**
 * *Seiteninhalte → Startseite: Über uns* ([[04-content]]): the homepage's
 * About teaser (the review's marker 9), its heading, its text and the image
 * beside them (Marcel, 2026-10-07). The defaults are the mockup's copy, so
 * the teaser reads the same until someone edits it. Kept on
 * `Page::for('home-about')`, the copy in `content`, the image in its media.
 */
final class HomeAboutSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::text('title')->label('Titel')->required(),
			Field::richtext('text')->label('Text')->required(),

			// The image beside the copy: the first one, or the one marked *Vorschau*.
			Field::section('Bild', [Field::custom('images')->with(['owner' => 'pages'])])->with(['open' => true]),
		];
	}

	public function defaults(): array
	{
		return [
			'title' => 'Warum bei der VIAK',
			'text' => '<p>Die Visualisierungs-Akademie ist der Kursbetrieb von Nightnurse Images, einem der bekanntesten Studios für Architekturvisualisierung in der Schweiz. Unterrichtet wird in denselben Lofts, in denen täglich Bilder für Wettbewerbe und Immobilienprojekte entstehen.</p>'
				.'<p>Seit der Zusammenführung mit 3D-Software.ch bekommst du hier beides: den Kurs und die Lizenz, von Menschen, die die Werkzeuge selbst einsetzen.</p>',
		];
	}
}
