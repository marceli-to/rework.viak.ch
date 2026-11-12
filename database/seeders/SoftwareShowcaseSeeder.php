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
 * **The copy is the client's** since 2026-11-06 (their website texts for
 * Rhino, #70); until then it was ours, written to fill the page. Still not
 * run in production: the images and testimonials are made up for the layout.
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

		// The client's own copy since 2026-11-06 (`.rework/content/00_fertig_Webseitentexte_Rhino.docx`, #70).
		$software->setTranslations('subtitle', []);

		$software->setTranslations('short_description', ['de' => <<<'HTML'
			<p>Hochpräzise 3D-Modelliersoftware für Architektur, Produkt- und Industriedesign sowie die Uhren- und Schmuckindustrie. Mit Rhino erstellst du dank exakter Kurven, Flächen und Volumen hochwertige 3D-Modelle, bereit für Planung, Visualisierung und Fertigung mit 3D-Druck oder CNC.</p>
			HTML]);

		$software->setTranslations('audiences', ['de' => 'Architektur- & Ingenieurbüros, Yachtdesign, Uhren- & Schmuckdesign, Industrial Design, Landschaftsarchitektur']);
		$software->setTranslations('intro', ['de' => 'Die Visualisierungs-Akademie bietet als Kompetenzzentrum für digitales Gestalten öffentliche & individuelle Schulungen sowie Software-Lizenzen von Rhino an:']);
		$software->setTranslations('courses_intro', ['de' => 'Lerne, wie du in der Praxis professionelle Modelle für Architektur, Produkt- und Industriedesign erstellst.']);
		$software->setTranslations('licences_intro', ['de' => 'Kaufe deine Rhino Lizenz direkt bei uns im Shop. Unsicher, welche Lizenz für dich passend ist? Melde dich bei uns. Wir helfen gerne weiter.']);
		$software->setTranslations('quote', ['de' => 'Die passende Software und das Wissen, sie effizient zu nutzen. Die Visualisierungs-Akademie Schweiz bietet beides, aus einer Hand.']);

		// *Was ist Rhinoceros?*, a page of its own.
		$software->setTranslations('full_description', ['de' => <<<'HTML'
			<p>Rhinoceros, kurz Rhino oder Rhino3D, ist eine kommerzielle CAD-Software, entwickelt von Robert McNeel &amp; Associates.</p>
			<h2>Woher kommt Rhino?</h2>
			<p>Hinter Rhino steht Robert McNeel &amp; Associates, ein 1980 gegründetes Softwareunternehmen mit Sitz in Seattle. Die Firma ist bis heute in Privatbesitz und finanziert sich ausschliesslich aus eigenen Erträgen. Sie ist mit Niederlassungen von Barcelona bis Tokio sowie über 700 Händlern und Schulungspartnern weltweit präsent. Einer davon ist die Visualisierungs-Akademie Schweiz GmbH in Zürich.</p>
			<h2>Für wen ist Rhino?</h2>
			<p>Rhino wird für DesignerInnen, IngenieurInnen und Fertigungsfachleute entwickelt. Architekturbüros nutzen es für Freiform- und Wettbewerbsmodelle, Produkt- und IndustriedesignerInnen von Möbel- bis zu Bootsrumpf-Modellen. In der Uhren- und Schmuckbranche ist Rhino weit verbreitet. Dazu kommen Modellbau, 3D-Druck und CNC-Fertigung: überall dort, wo aus einer Idee ein exaktes, produzierbares Modell werden soll.</p>
			HTML]);

		$software->setTranslations('information', []);

		$software->setTranslations('seo_description', ['de' => 'Rhinoceros 9 kaufen und lernen: Lizenzen für Windows und macOS und Rhino-Kurse in Zürich, bei der Visualisierungs-Akademie.']);
		$software->setTranslations('seo_tags', ['de' => 'Rhino, Rhinoceros, Rhino 9, Grasshopper, SubD, NURBS, Lizenz, Kurs']);
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
