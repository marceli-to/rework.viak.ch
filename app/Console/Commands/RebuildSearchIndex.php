<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Search\SearchIndex;
use Illuminate\Console\Command;

/**
 * `search_entries` from scratch ([[15-search]], [[SearchIndex]]). Saving a
 * record keeps it current; this is for when records arrive without being
 * saved through Eloquent: after the ports at cutover, after a database
 * import, or to mend a row that slipped. Safe to rerun at any time.
 */
class RebuildSearchIndex extends Command
{
	protected $signature = 'search:rebuild';

	protected $description = 'Rebuild the site search index';

	public function handle(SearchIndex $index): int
	{
		$this->components->info("{$index->rebuild()} search entries written.");

		return self::SUCCESS;
	}
}
