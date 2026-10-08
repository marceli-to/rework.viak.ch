<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Manufacturer;
use App\Models\Software;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The licence catalogue from `database/data/licences.json` ([[05-licences]]):
 * the client's list of 2026-10-08, 107 rows, grouped into products as the
 * chunk doc proposes. The JSON *is* the grouping; the spreadsheet stays
 * outside the repo.
 *
 * **Rerunnable.** A variant is known by its article number, a product by its
 * title within its group, a group and a maker by their German title. What
 * exists is left alone, so VIAK's own edits in the dashboard survive a rerun;
 * `--refresh` writes the file over them, for the corrections that arrive
 * before launch (#47–49), and moves a variant to the product the file puts
 * it under.
 */
class ImportLicences extends Command
{
	protected $signature = 'licences:import
		{file=database/data/licences.json : The catalogue, grouped}
		{--refresh : Overwrite existing products and variants from the file}';

	protected $description = 'Import the licence catalogue';

	/** @var array<string, int> */
	private array $counts = ['software' => 0, 'manufacturers' => 0, 'products' => 0, 'variants' => 0, 'updated' => 0];

	public function handle(): int
	{
		$path = base_path((string) $this->argument('file'));

		if (! is_file($path)) {
			$this->components->error("No catalogue at {$path}.");

			return self::FAILURE;
		}

		$groups = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

		DB::transaction(function () use ($groups): void {
			foreach ($groups as $group) {
				$software = $this->software($group['software']);

				foreach ($group['products'] as $position => $row) {
					$product = $this->product($software, $row, $position + 1);

					foreach ($row['variants'] as $order => $variant) {
						$this->variant($product, $variant, $order + 1);
					}
				}
			}
		});

		$this->components->info(sprintf(
			'Created %d software group(s), %d manufacturer(s), %d product(s), %d variant(s); %d updated.',
			$this->counts['software'], $this->counts['manufacturers'], $this->counts['products'], $this->counts['variants'], $this->counts['updated'],
		));

		return self::SUCCESS;
	}

	/** An existing group by its exact name, as the course software has it (CINEMA 4D); a new one goes to the end. */
	private function software(string $title): Software
	{
		$existing = Software::query()->where('title->de', $title)->first();

		if ($existing) {
			return $existing;
		}

		$this->counts['software']++;

		return Software::create(['title' => ['de' => $title], 'order' => (int) Software::max('order') + 1, 'publish' => true]);
	}

	private function manufacturer(string $title): Manufacturer
	{
		$existing = Manufacturer::query()->where('title->de', $title)->first();

		if ($existing) {
			return $existing;
		}

		$this->counts['manufacturers']++;

		return Manufacturer::create(['title' => ['de' => $title], 'order' => (int) Manufacturer::max('order') + 1, 'publish' => true]);
	}

	/** @param  array<string, mixed>  $row */
	private function product(Software $software, array $row, int $order): LicenceProduct
	{
		$attributes = [
			'manufacturer_id' => $this->manufacturer($row['manufacturer'])->id,
			'hosts' => $row['hosts'],
			'three_years_on_request' => $row['three_years_on_request'],
			'order' => $order,
		];

		$product = $software->products()->where('title->de', $row['title'])->first();

		if ($product === null) {
			$this->counts['products']++;

			return $software->products()->create([
				...$attributes,
				'title' => ['de' => $row['title']],
				'slug' => LicenceProduct::freeSlug($row['title']),
				'publish' => true,
			]);
		}

		if ($this->option('refresh')) {
			$product->update($attributes);
		}

		return $product;
	}

	/** @param  array<string, mixed>  $row */
	private function variant(LicenceProduct $product, array $row, int $order): void
	{
		$attributes = [
			'licence_product_id' => $product->id,
			'title' => ['de' => $row['title']],
			'price' => $row['price'],
			'licence_type' => $row['licence_type'],
			'access' => $row['access'],
			'platforms' => $row['platforms'],
			'note' => $row['note'] === null ? null : ['de' => $row['note']],
			'min_quantity' => $row['min_quantity'],
			'listed' => $row['listed'],
			'order' => $order,
		];

		$variant = LicenceVariant::query()->where('sku', $row['sku'])->first();

		if ($variant === null) {
			$this->counts['variants']++;
			LicenceVariant::create([...$attributes, 'sku' => $row['sku']]);
		} elseif ($this->option('refresh')) {
			$this->counts['updated']++;
			$variant->update($attributes);
		}
	}
}
