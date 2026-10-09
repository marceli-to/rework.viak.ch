<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Media;
use App\Models\Software;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One software page with everything on it, for a dev database
 * ([[05-licences]]): `php artisan db:seed --class=SoftwareShowcaseSeeder`.
 * Safe to run again.
 *
 * **The copy is ours, written to fill the page**, not VIAK's: no software
 * has copy yet, in legacy or the mockups. So this is not run in production.
 * Rhinoceros, because it has the most courses. The images are copies of its
 * beginners' course's, the categories its courses', and the two
 * *Kundenmeinungen* are invented for the layout.
 */
class SoftwareShowcaseSeeder extends Seeder
{
	private const SLUG = 'rhinoceros';

	/** Where the images come from: *Rhino Einstiegskurs*. */
	private const COURSE = 7;

	public function run(): void
	{
		$software = Software::query()->where('slug->de', self::SLUG)->firstOrFail();

		$software->setTranslations('subtitle', ['de' => '3D-Modellierung für Design, Architektur und Fertigung']);

		$software->setTranslations('short_description', ['de' => <<<'HTML'
			<p>Rhinoceros, kurz Rhino, ist das 3D-Programm für alle, die präzise Freiformen brauchen: vom Schmuckstück über das Möbel bis zur Fassade. Rhino modelliert mit NURBS-Flächen und SubD, liest und schreibt die gängigen CAD-Formate und läuft auf Windows und macOS.</p>
			<p>Bei uns bekommen Sie die Lizenz und den Kurs dazu aus einer Hand.</p>
			HTML]);

		$software->setTranslations('full_description', ['de' => <<<'HTML'
			<p>Rhino wird von Robert McNeel &amp; Associates entwickelt und ist seit über 25 Jahren im Einsatz, in Designbüros, Architekturbüros, Ateliers und in der Industrie. Es verbindet die Genauigkeit eines CAD-Programms mit der Freiheit eines Modellierers: Kurven und Flächen lassen sich exakt bemassen und trotzdem frei formen.</p>
			<p>Mit Plugins wie V-Ray, Enscape, VisualARQ oder Lands Design wird Rhino zur Plattform für Visualisierung, BIM und Landschaftsarchitektur.</p>
			<p><strong>Was Rhino 8 mitbringt</strong></p>
			<ul>
				<li>NURBS-Modellierung für exakte, fertigungstaugliche Geometrie</li>
				<li>SubD für organische Formen, die sich in NURBS umwandeln lassen</li>
				<li>Grasshopper, die visuelle Programmierung für parametrisches Design, ist eingebaut</li>
				<li>Import und Export von DWG, DXF, STEP, IGES, OBJ, STL und vielen weiteren Formaten</li>
				<li>Werkzeuge für Zeichnungen, Bemassung und Layouts</li>
			</ul>
			HTML]);

		$software->setTranslations('information', ['de' => <<<'HTML'
			<p>Sie sind nicht sicher, welche Lizenz zu Ihnen passt? Rufen Sie uns an oder schreiben Sie uns, wir beraten Sie gerne.</p>
			<p>Eine Dauerlizenz läuft unbefristet und lässt sich auf die nächste Version aktualisieren. Für Schulen und Studierende gibt es eigene Lizenzen, die wir auf Anfrage anbieten.</p>
			HTML]);

		$software->setTranslations('seo_description', ['de' => 'Rhinoceros 8 kaufen und lernen: Lizenzen für Windows und macOS und Rhino-Kurse in Zürich, bei der Visualisierungs-Akademie.']);
		$software->setTranslations('seo_tags', ['de' => 'Rhino, Rhinoceros, Rhino 8, Grasshopper, SubD, NURBS, Lizenz, Kurs']);
		$software->save();

		$this->images($software);

		$software->categories()->sync(
			$software->courses()->with('categories')->get()->flatMap->categories->pluck('id')->unique()->values()
		);

		$this->testimonials($software);
	}

	/** A visual, a teaser and an Open Graph image, copied from the course's own. */
	private function images(Software $software): void
	{
		if ($software->media()->exists()) {
			return;
		}

		$course = Course::query()->with('media')->findOrFail(self::COURSE);
		$disk = Storage::disk('public');

		$picks = [
			[$course->visuals()->first(), []],
			[$course->teaser(), ['is_teaser' => true]],
			[$course->openGraph(), ['is_og' => true]],
		];

		foreach ($picks as $order => [$source, $flags]) {
			if (! $source instanceof Media) {
				continue;
			}

			$file = uniqid().'_'.$source->original_name;
			$disk->copy('uploads/'.$source->file, 'uploads/'.$file);

			$software->media()->create([
				...$source->only(['original_name', 'mime_type', 'size', 'width', 'height', 'crop', 'variant']),
				'uuid' => (string) Str::uuid(),
				'file' => $file,
				'alt' => 'Rhinoceros',
				'is_teaser' => false,
				'is_og' => false,
				'sort_order' => $order,
				...$flags,
			]);
		}
	}

	private function testimonials(Software $software): void
	{
		$quotes = [
			['name' => 'Andrea Keller', 'context' => 'Produktdesignerin, Winterthur',
				'quote' => 'Lizenz und Einstiegskurs am gleichen Ort, das hat mir viel Zeit gespart. Nach zwei Tagen habe ich meine ersten Entwürfe selbst modelliert.'],
			['name' => 'Marco Rossi', 'context' => 'Architekt, Lugano',
				'quote' => 'Wir arbeiten im Büro mit der Netzwerklizenz. Die Beratung war ehrlich: wir haben genau so viele Plätze gekauft, wie wir brauchen.'],
		];

		foreach ($quotes as $order => $quote) {
			$testimonial = Testimonial::query()->firstOrNew([
				'subject_type' => $software->getMorphClass(),
				'subject_id' => $software->id,
				'name' => $quote['name'],
			]);

			$testimonial->fill(['publish' => true, 'order' => $order]);
			$testimonial->setTranslations('quote', ['de' => $quote['quote']]);
			$testimonial->setTranslations('context', ['de' => $quote['context']]);
			$testimonial->save();
		}
	}
}
