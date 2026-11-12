<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Software;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The client's website texts for the Angebot pages (#70, 2026-11-06,
 * [[14-angebote]] *The website texts*), from `database/data/texts.json`:
 * the Angebot page's texts and its *Was ist …?* page, by the Angebot's
 * German title. The file was read out of the docs in `.rework/content/`
 * (each entry names its doc); their course and licence pages are not in it.
 *
 * **Rerunnable**: only an empty field is filled, so what VIAK has written in
 * the dashboard survives a rerun. `--force` makes the file win.
 *
 * Not in the file: the plugins (#72) and the Angebote that have no record
 * yet (#73).
 */
class ImportWebsiteTexts extends Command
{
	protected $signature = 'catalogue:texts
		{file=database/data/texts.json : The texts, by Angebot title}
		{--dry-run : Report what would change without writing}
		{--force : The file wins: overwrite fields that already hold a text}';

	protected $description = 'Fill the Angebot pages with the client\'s website texts';

	/** What the file may set; anything else in an entry (its doc) is ignored. */
	private const FIELDS = ['short_description', 'audiences', 'intro', 'courses_intro', 'licences_intro', 'quote', 'full_description', 'licences_lead', 'licences_quote'];

	public function handle(): int
	{
		$path = base_path((string) $this->argument('file'));

		if (! is_file($path)) {
			$this->components->error("No file at {$path}.");

			return self::FAILURE;
		}

		$file = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
		$rows = [];
		$missing = [];

		DB::beginTransaction();

		foreach ($file as $title => $texts) {
			$software = Software::query()->where('title->de', $title)->first();

			if (! $software) {
				$missing[] = $title;

				continue;
			}

			$filled = [];
			$kept = [];

			foreach (self::FIELDS as $field) {
				$text = trim((string) ($texts[$field] ?? ''));

				if ($text === '') {
					continue;
				}

				$current = trim((string) $software->getTranslation($field, 'de', false));

				if ($current === $text) {
					continue;
				}

				if ($current !== '' && ! $this->option('force')) {
					$kept[] = $field;

					continue;
				}

				$software->setTranslation($field, 'de', $text);
				$filled[] = $field;
			}

			$software->save();
			$rows[] = [$title, count($filled), implode(', ', $kept)];
		}

		$this->table(['Angebot', 'filled', 'kept (already has a text)'], $rows);

		foreach ($missing as $title) {
			$this->components->warn("No Angebot «{$title}»: skipped.");
		}

		if ($this->option('dry-run')) {
			DB::rollBack();
			$this->components->warn('Dry run: nothing written.');
		} else {
			DB::commit();
		}

		return self::SUCCESS;
	}
}
