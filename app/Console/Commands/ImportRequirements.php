<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LicenceVariant;
use App\Models\Software;
use App\Support\EditorHtml;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * **Systemvoraussetzungen** (Oliver, 2026-11-26, [[14-angebote]]):
 * `database/data/requirements.csv`, one HTML block per product, keyed by
 * article number (`Artikelnr;Produkt;Systemvoraussetzungen_HTML`, `;`-separated,
 * UTF-8 with BOM), into `software.requirements`, which is **once per Angebot**
 * (the client, 2026-11-12).
 *
 * An article number finds its variant, the variant its Angebot. An Angebot
 * whose numbers carry one text gets it as it is. One with several products
 * of its own (V-Ray: V-Ray, Vantage, Phoenix, Scans; Lumion View and Pro;
 * RealFlow 10.5 and the Plugin) gets each product's block under an `h3` with
 * the product's name, in the file's order. Cinema 4D and Maxon One share
 * one text and each get it whole.
 *
 * Rerunnable: an Angebot that has requirements keeps them (an edit in the
 * dashboard included) unless `--force`. Unknown article numbers are reported.
 */
class ImportRequirements extends Command
{
	protected $signature = 'catalogue:requirements
		{--force : Replace requirements an Angebot already has}
		{--dry-run : Report what would change without writing}';

	protected $description = 'Give the Angebote the Systemvoraussetzungen in database/data/requirements.csv';

	public function handle(): int
	{
		/** @var array<int, array<string, Collection<string, string>>> $texts Angebot id => product => html */
		$texts = [];
		$unknown = [];

		foreach ($this->rows() as [$sku, $product, $html]) {
			$software = LicenceVariant::query()->where('sku', $sku)->with('product.software')->first()?->product?->software;

			if ($software === null) {
				$unknown[] = "{$sku} ({$product})";

				continue;
			}

			$texts[$software->id][$product] = $html;
		}

		foreach ($unknown as $line) {
			$this->components->warn("{$line}: no licence with this article number");
		}

		$written = 0;

		foreach (Software::query()->whereIn('id', array_keys($texts))->get() as $software) {
			if (filled(strip_tags((string) $software->getTranslation('requirements', 'de', false))) && ! $this->option('force')) {
				continue;
			}

			$this->line('  '.$software->getTranslation('title', 'de').': '.implode(', ', array_keys($texts[$software->id])));

			if (! $this->option('dry-run')) {
				$software->setTranslation('requirements', 'de', $this->combine($texts[$software->id]))->save();
			}

			$written++;
		}

		$this->components->info(($this->option('dry-run') ? 'Would set ' : 'Set ')."{$written} Angebote.");

		return self::SUCCESS;
	}

	/** @param array<string, string> $products */
	private function combine(array $products): string
	{
		if (count(array_unique($products)) === 1) {
			return (string) EditorHtml::sanitize(reset($products));
		}

		return (string) EditorHtml::sanitize(collect($products)
			->map(fn (string $html, string $product) => '<h3>'.e($product).'</h3>'.$html)
			->implode(''));
	}

	/** @return list<array{string, string, string}> */
	private function rows(): array
	{
		$handle = fopen(database_path('data/requirements.csv'), 'r');
		$rows = [];
		$header = true;

		while (($row = fgetcsv($handle, null, ';', '"', '')) !== false) {
			if ($header) {
				$header = false;

				continue;
			}

			if (count($row) >= 3 && trim($row[0]) !== '') {
				$rows[] = [trim($row[0]), trim($row[1]), trim($row[2])];
			}
		}

		fclose($handle);

		return $rows;
	}
}
