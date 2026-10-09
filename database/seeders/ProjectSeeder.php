<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Project;
use App\Models\Software;
use App\Support\Slug;
use Illuminate\Database\Seeder;

/**
 * The six Vorhaben of the mockups, for a dev database ([[04-content]]):
 * `php artisan db:seed --class=ProjectSeeder`. Safe to run again.
 *
 * **The copy is the mockups' filler**, and only Räume was discussed in the
 * review; which of the six are real is `Open-Questions.md` #4. So this is not
 * run in production, where VIAK enters its own. The courses are the mockups'
 * offer lists, matched to the catalogue by number where a course exists,
 * and the software their tools lines name, by slug where it has a page.
 */
class ProjectSeeder extends Seeder
{
	private const PROJECTS = [
		[
			'title' => 'Räume visualisieren',
			'teaser' => 'Enscape, Twinmotion, Lumion, V-Ray',
			'lead' => 'Ein Projekt überzeugend zeigen, bevor es gebaut ist: für die Bauherrschaft, den Wettbewerb, die Vermarktung.',
			'text' => [
				'Für Architekt:innen, Innenarchitekt:innen, Planer:innen und Studierende, die aus einem Modell in kurzer Zeit Bilder und Rundgänge machen wollen, die eine Entscheidung auslösen. Licht, Material und Bildaufbau zählen dabei mehr als das Werkzeug.',
				'Nach einem Kurs hier erstellst du aus Archicad, Revit, SketchUp oder Rhino eigene Renderings und kurze Animationen und weisst, welche Software zu deinem Büro passt. Die Lizenz dazu bekommst du direkt bei uns.',
			],
			'courses' => [23, 35, 29, 16, 10, 15, 14],
			'software' => ['enscape', 'twinmotion', 'lumion', 'v-ray'],
		],
		[
			'title' => 'Objekte entwerfen',
			'teaser' => 'Rhino, Grasshopper, SubD, KeyShot',
			'lead' => 'Von der Idee zu einem Modell, das sich fertigen lässt: gefräst, gedruckt, gegossen, produziert.',
			'text' => [
				'Für Produkt- und Industriedesigner:innen, Goldschmied:innen, Möbelbauer:innen und Ingenieur:innen, die präzise Geometrie brauchen und ihre Entwürfe überzeugend präsentieren wollen.',
				'Nach dem Einstiegskurs modellierst du in Rhino eigene Objekte für die Fertigung; mit SubD und Grasshopper kommen Freiformen und parametrisches Design dazu. KeyShot macht aus den CAD-Daten in Minuten ein Produktbild.',
			],
			'courses' => [7, 26, 8, 2, 28, 9],
			'software' => ['rhinoceros', 'keyshot'],
		],
		[
			'title' => 'Bilder gestalten',
			'teaser' => 'Architekturfotografie, Photoshop, Komposition und Licht',
			'lead' => 'Das Handwerk hinter starken Bildern: Komposition, Licht, Kamera und Nachbearbeitung, unabhängig vom Werkzeug.',
			'text' => [
				'Für Architekt:innen, Fotograf:innen, Visualisierer:innen und alle, die Gebäude, Räume oder Produkte so zeigen wollen, dass man hinschaut. Software ist hier Mittel, nicht Thema.',
				'Nach diesen Kursen fotografierst du Architektur mit Plan statt Zufall, beurteilst Renderings nach Bildaufbau und Licht und bearbeitest Bilder in Photoshop so, dass sie ihre Wirkung behalten.',
			],
			'courses' => [17, 10, 3, 16],
			'software' => ['veras', 'hdr-light-studio', 'v-ray', 'maxwell-render'],
		],
		[
			'title' => 'Bewegtbild erstellen',
			'teaser' => 'After Effects, Premiere Pro, Animation in Twinmotion',
			'lead' => 'Animation, Schnitt und Echtzeit: Projekte in Bewegung bringen, vom Kamerafahrt-Rendering bis zum fertigen Video.',
			'text' => [
				'Für Motion Designer:innen, Videoeditor:innen, Content Creator und Architekt:innen, die aus Modellen und Aufnahmen Filme machen, für Social Media, Präsentationen oder Games.',
				'Nach den Einstiegskursen schneidest du in Premiere Pro, animierst in After Effects und weisst, wo Echtzeit-Engines wie Twinmotion oder Unreal ins Spiel kommen. Für Animation in 3D gibt es Blender und Cinema 4D.',
			],
			'courses' => [11, 27, 32, 4, 23, 33, 18],
			'software' => ['twinmotion', 'unreal-engine', 'cinema-4d'],
		],
		[
			'title' => 'Mit KI gestalten',
			'teaser' => 'Chaos Veras, KI-Workflows für Bild und Video',
			'lead' => 'Kontrollierte, wiederholbare Resultate aus generativen Werkzeugen, statt Zufallstreffer.',
			'text' => [
				'Für Architekt:innen, Designer:innen und Video Creator, die KI schon ausprobiert haben und jetzt wollen, dass dasselbe Gebäude zweimal gleich aussieht, ein Material hält und ein Brief getroffen wird.',
				'Die Kurse bauen auf euren bestehenden Programmen auf: Veras in Archicad, Enscape oder SketchUp, KI-Werkzeuge in Photoshop und Premiere Pro, eigene Abläufe mit Figma Weave. Was in der Werkstatt täglich läuft, zeigen wir so, wie es dort läuft.',
			],
			'courses' => [32, 29, 16, 1, 40],
			'software' => ['veras', 'myarchitectai'],
		],
		[
			'title' => 'Teams & Unternehmen',
			'teaser' => 'Visual Facilitation, LEGO Serious Play, Graphic Recording',
			'lead' => 'Methoden, mit denen Teams Ideen sichtbar machen, Entscheidungen treffen und Strategien gemeinsam entwickeln.',
			'text' => [
				'Für Führungskräfte, Projektleiter:innen, Moderator:innen, Berater:innen und alle, die Workshops, Retrospektiven oder Strategieprozesse leiten. Hier geht es nicht um Software, sondern um Stift, Papier, Bausteine und Haltung.',
				'Die Kurse bauen aufeinander auf: Visual Facilitator in fünf Teilen vom ersten Strich bis zum Graphic Recording, LEGO Serious Play in drei Stufen bis zur zertifizierten Moderation. Alle Kurse sind offen buchbar. Wer die Methoden im eigenen Unternehmen einführen will, findet unter Firmenschulung das passende Format.',
			],
			'courses' => [70, 71, 73, 74, 75, 21, 80, 40],
			'software' => [],
		],
	];

	public function run(): void
	{
		foreach (self::PROJECTS as $position => $data) {
			$slug = Slug::forTitles($data['title']);

			$project = Project::query()->where('slug->de', $slug['de'])->first() ?? new Project(['slug' => $slug]);

			$project->fill([
				'title' => ['de' => $data['title']],
				'teaser' => ['de' => $data['teaser']],
				'lead' => ['de' => $data['lead']],
				'text' => ['de' => collect($data['text'])->map(fn (string $paragraph) => "<p>{$paragraph}</p>")->implode('')],
				'publish' => true,
				'order' => $position + 1,
			])->save();

			$ids = Course::query()->whereIn('number', $data['courses'])->pluck('id', 'number');

			$project->courses()->sync(collect($data['courses'])
				->filter(fn (int $number) => isset($ids[$number]))
				->values()
				->mapWithKeys(fn (int $number, int $position) => [$ids[$number] => ['order' => $position + 1]])
				->all());

			$software = Software::query()->get()->keyBy(fn (Software $item) => $item->getTranslation('slug', 'de'));

			$project->software()->sync(collect($data['software'])
				->filter(fn (string $slug) => $software->has($slug))
				->values()
				->mapWithKeys(fn (string $slug, int $position) => [$software[$slug]->id => ['order' => $position + 1]])
				->all());
		}
	}
}
