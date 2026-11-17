<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\News;
use App\Models\Software;
use Illuminate\Console\Command;

/**
 * **The homepage's first news** ([[News]], 2026-11-17): the two items in
 * `database/data/news.json`, published, in the file's order, so staging and
 * the cutover show the news box before VIAK writes its own. Rerunnable: an
 * item whose title is there already is left as it is (VIAK may have edited
 * it), and a new one goes below what exists.
 *
 * The file names a link by slug (`software:rhinoceros`, `course:{slug}`) or
 * page (`page:training`); the database keeps the uuid ([[NewsLink]]). A slug
 * with no record leaves the item without a link, and says so.
 */
class ImportNews extends Command
{
	protected $signature = 'catalogue:news
		{file=database/data/news.json : The news items}
		{--dry-run : Report what would change without writing}';

	protected $description = 'Add the homepage\'s first news items, published';

	public function handle(): int
	{
		$items = json_decode((string) file_get_contents(base_path($this->argument('file'))), true, flags: JSON_THROW_ON_ERROR);
		$written = 0;
		$order = (int) News::max('order');

		foreach ($items as $item) {
			if (News::query()->where('title->de', $item['title'])->exists()) {
				continue;
			}

			$link = $this->link($item['link'] ?? null, $item['title']);

			if (! $this->option('dry-run')) {
				News::create([
					'title' => ['de' => $item['title']],
					'text' => ['de' => $item['text'] ?? null],
					'link' => $link,
					'publish' => true,
					'order' => ++$order,
				]);
			}

			$written++;
		}

		$this->components->info(($this->option('dry-run') ? 'Would add ' : 'Added ')."{$written} news items.");

		return self::SUCCESS;
	}

	/** A slug in the file as the key the database keeps, or null. */
	private function link(?string $link, string $title): ?string
	{
		[$kind, $id] = array_pad(explode(':', (string) $link, 2), 2, null);

		$uuid = match ($kind) {
			'page' => null,
			'software' => Software::query()->where('slug->de', $id)->value('uuid'),
			'course' => Course::query()->where('slug->de', $id)->value('uuid'),
			default => null,
		};

		if ($kind === 'page') {
			return $link;
		}

		if ($link && $uuid === null) {
			$this->components->warn("{$title}: no record for {$link}, added without a link");
		}

		return $uuid ? "{$kind}:{$uuid}" : null;
	}
}
