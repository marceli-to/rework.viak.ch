<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Software;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * **The Angebote' logos** (Oliver, 2026-11-17, [[14-angebote]] *Logos*):
 * `database/data/logos/{slug}.png`, his 250px files named by the Angebot's
 * slug, onto the public disk and into `software.logo`. Rerunnable: an
 * Angebot that has a logo keeps it (one VIAK uploaded in the dashboard
 * included) unless `--force`. A file with no Angebot is reported.
 */
class ImportLogos extends Command
{
	protected $signature = 'catalogue:logos
		{--force : Replace a logo an Angebot already has}
		{--dry-run : Report what would change without writing}';

	protected $description = 'Give the Angebote the logos in database/data/logos';

	public function handle(): int
	{
		$written = 0;

		foreach (glob(database_path('data/logos/*.png')) ?: [] as $path) {
			$slug = basename($path, '.png');
			$software = Software::query()->where('slug->de', $slug)->first();

			if ($software === null) {
				$this->components->warn("{$slug}: no Angebot with this slug");

				continue;
			}

			if ($software->logo && ! $this->option('force')) {
				continue;
			}

			if (! $this->option('dry-run')) {
				$name = $slug.'-'.Str::lower(Str::random(8)).'.png';
				Storage::disk('public')->put(Software::LOGOS.'/'.$name, (string) file_get_contents($path));
				$software->replaceLogo($name);
			}

			$written++;
		}

		$this->components->info(($this->option('dry-run') ? 'Would set ' : 'Set ')."{$written} logos.");

		return self::SUCCESS;
	}
}
