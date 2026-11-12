<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Courses\CreateCourse;
use App\Models\Category;
use App\Models\Course;
use App\Models\Software;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The Angebote and courses the client is still working on (Oliver,
 * 2026-11-12, [[14-angebote]] *The answers of 2026-11-12*), from
 * `database/data/offers.json`: in the database, **unpublished**, so VIAK
 * finishes and publishes them in the dashboard.
 *
 * - `software`: new Angebote (ComfyUI, D5 Render, Figma Weave). Their page
 *   texts come from `catalogue:texts`, their categories from
 *   `catalogue:categories`; run both after this.
 * - `courses`: one per course page in the docs, with the doc's title (its H1
 *   split at the colon into title and subtitle), short description, the four
 *   facts and *Weitere Informationen*. **The fee is 0**: the docs give none,
 *   VIAK sets it before publishing. Two replace a course that stays online
 *   until then (`replaces`, for the record only).
 * - `topics`: **Angebote without a program** (#73: Architekturvisualisierung,
 *   Architekturfotografie, KI-Video), published, tagged *Kurs* by their
 *   courses ([[Software::offer]]). Each named course takes its topic in place
 *   of the programs under `replaces` (Photoshop, which Oliver hides: its
 *   Angebot then has nothing published and leaves the site); its other
 *   programs stay (KI-Video's course under After Effects and Premiere).
 * - `unpublish`: courses taken off the site. *DaVinci Resolve
 *   Einführungskurs* is the only one: without it DaVinci has nothing to
 *   show and its Angebot leaves the site with it ([[Software::scopeOnSite]]).
 *
 * **Rerunnable**: a software or course that exists by its German title is
 * left alone. The unpublishing is applied on every run, so run this at the
 * cutover (after the ports, which bring the DaVinci course back as legacy
 * has it, and the courses' programs as legacy tags them) and not again once VIAK has decided otherwise.
 */
class ImportNewOffers extends Command
{
	protected $signature = 'catalogue:offers
		{file=database/data/offers.json : The new Angebote and courses}
		{--dry-run : Report what would change without writing}';

	protected $description = 'Add the client\'s unfinished Angebote and courses, unpublished';

	public function handle(CreateCourse $create): int
	{
		$path = base_path((string) $this->argument('file'));

		if (! is_file($path)) {
			$this->components->error("No file at {$path}.");

			return self::FAILURE;
		}

		$file = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
		$rows = [];

		DB::beginTransaction();

		foreach ($file['software'] as $title) {
			if (Software::query()->where('title->de', $title)->exists()) {
				$rows[] = ['Angebot', $title, 'exists'];

				continue;
			}

			Software::create(['title' => ['de' => $title], 'order' => (int) Software::max('order') + 1, 'publish' => false]);
			$rows[] = ['Angebot', $title, 'created, unpublished'];
		}

		foreach ($file['courses'] as $entry) {
			$rows[] = ['Kurs', $entry['title'], $this->course($create, $entry)];
		}

		foreach ($file['topics'] ?? [] as $topic) {
			$rows[] = ['Thema', $topic['title'], $this->topic($topic)];
		}

		foreach ($file['unpublish'] as $title) {
			$changed = Course::query()->where('title->de', $title)->where('publish', true)->update(['publish' => false]);
			$rows[] = ['Kurs', $title, $changed ? 'unpublished' : 'not published already'];
		}

		$this->table(['', 'Titel', ''], $rows);

		if ($this->option('dry-run')) {
			DB::rollBack();
			$this->components->warn('Dry run: nothing written.');
		} else {
			DB::commit();
		}

		return self::SUCCESS;
	}

	/** @param  array{title: string, courses: array<int, string>, replaces: array<int, string>}  $topic */
	private function topic(array $topic): string
	{
		$software = Software::query()->where('title->de', $topic['title'])->first();
		$created = $software === null;
		$software ??= Software::create(['title' => ['de' => $topic['title']], 'order' => (int) Software::max('order') + 1, 'publish' => true]);

		$replaced = Software::query()->whereIn('title->de', $topic['replaces'])->pluck('id');
		$moved = 0;

		foreach (Course::query()->whereIn('title->de', $topic['courses'])->get() as $course) {
			$course->software()->syncWithoutDetaching([$software->id]);
			$course->software()->detach($replaced);
			$moved++;
		}

		return ($created ? 'created, published' : 'exists').", {$moved} course(s) under it";
	}

	/** @param  array<string, mixed>  $entry */
	private function course(CreateCourse $create, array $entry): string
	{
		if (Course::query()->where('title->de', $entry['title'])->exists()) {
			return 'exists';
		}

		$course = $create->execute([
			'title' => ['de' => $entry['title']],
			'subtitle' => ['de' => $entry['subtitle']],
			'short_description' => ['de' => $entry['short_description']],
			'information_booking' => ['de' => $entry['information_booking']],
			'facts' => array_map(fn (string $fact) => ['de' => $fact], $entry['facts']),
			'fee' => 0,
			'online' => false,
			'publish' => false,
			'featured' => false,
			'order' => (int) Course::max('order') + 1,
		]);

		$software = Software::query()->whereIn('title->de', $entry['software'])->pluck('id');
		$missing = count($entry['software']) - $software->count();
		$course->software()->sync($software);
		$course->categories()->sync(Category::query()->whereIn('title->de', $entry['categories'])->pluck('id'));

		return 'created, unpublished, fee 0'.($missing ? ", {$missing} software not found" : '');
	}
}
