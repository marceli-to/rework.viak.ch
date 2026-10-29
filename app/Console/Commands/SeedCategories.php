<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Course;
use App\Models\LicenceProduct;
use App\Models\Software;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The client's five categories in place of legacy's three ([[14-angebote]],
 * 2026-10-29), with a first assignment for VIAK to correct in the dashboard.
 * From `database/data/categories.json`.
 *
 * `port:courses` brings legacy's three back, so this runs after it at the
 * cutover (`Deploy.md`). **Rerunnable**: what already has a category is left
 * alone, so VIAK's own choices survive a rerun, and only the three named
 * under `retire` are removed, never a category VIAK added (#66 may add one).
 *
 * In this order: the five exist, published, in the file's order; the three
 * retire (unlinked, then deleted); a **software** gets the file's, as the
 * 2026-09-23 mockup's *Alle Angebote* sorts the tools (the ones it does not
 * show, by their kind); a **licence product** the file's where it names one
 * (the plugins sold under Rhinoceros), else its software's; a **course** its
 * software's, all of them. What is left without one is listed: on dev data,
 * the eight courses with no software (#66).
 */
class SeedCategories extends Command
{
	protected $signature = 'catalogue:categories
		{file=database/data/categories.json : The categories and the first assignment}
		{--dry-run : Report what would change without writing}';

	protected $description = 'Put the five Angebot categories in place of the legacy ones';

	/** @var array<string, int> */
	private array $counts = ['created' => 0, 'retired' => 0, 'software' => 0, 'products' => 0, 'courses' => 0];

	public function handle(): int
	{
		$path = base_path((string) $this->argument('file'));

		if (! is_file($path)) {
			$this->components->error("No file at {$path}.");

			return self::FAILURE;
		}

		$file = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

		DB::beginTransaction();

		$categories = $this->categories($file['categories']);
		$this->retire($file['retire']);
		$this->software($file['software'], $categories);
		$this->products($file['products'], $categories);
		$this->courses();

		$this->table(['created', 'retired', 'software', 'products', 'courses'], [$this->counts]);
		$this->line('software, products, courses: how many were given categories; the rest kept theirs.');
		$this->report();

		if ($this->option('dry-run')) {
			DB::rollBack();
			$this->components->warn('Dry run: nothing written.');
		} else {
			DB::commit();
		}

		return self::SUCCESS;
	}

	/**
	 * @param  list<string>  $titles
	 * @return array<string, int> id by German title
	 */
	private function categories(array $titles): array
	{
		$ids = [];

		foreach ($titles as $position => $title) {
			$category = Category::withTrashed()->where('title->de', $title)->first();

			if (! $category) {
				$category = new Category(['title' => ['de' => $title]]);
				$this->counts['created']++;
			}

			$category->fill(['order' => $position + 1, 'publish' => true]);
			$category->deleted_at = null;
			$category->save();

			$ids[$title] = $category->id;
		}

		return $ids;
	}

	/** @param list<string> $titles */
	private function retire(array $titles): void
	{
		foreach (Category::query()->whereIn('title->de', $titles)->get() as $category) {
			$category->courses()->detach();
			DB::table('software_category')->where('category_id', $category->id)->delete();
			DB::table('licence_product_category')->where('category_id', $category->id)->delete();
			$category->delete();
			$this->counts['retired']++;
		}
	}

	/**
	 * @param  array<string, list<string>>  $map
	 * @param  array<string, int>  $categories
	 */
	private function software(array $map, array $categories): void
	{
		foreach (Software::query()->with('categories')->get() as $software) {
			$titles = $map[$software->getTranslation('title', 'de')] ?? [];

			if ($software->categories->isNotEmpty() || $titles === []) {
				continue;
			}

			$software->categories()->attach($this->ids($titles, $categories));
			$this->counts['software']++;
		}
	}

	/**
	 * @param  array<string, list<string>>  $map
	 * @param  array<string, int>  $categories
	 */
	private function products(array $map, array $categories): void
	{
		foreach (LicenceProduct::query()->with(['categories', 'software.categories'])->get() as $product) {
			if ($product->categories->isNotEmpty()) {
				continue;
			}

			$titles = $map[$product->getTranslation('title', 'de')] ?? null;
			$ids = $titles !== null
				? $this->ids($titles, $categories)
				: ($product->software?->categories->modelKeys() ?? []);

			if ($ids !== []) {
				$product->categories()->attach($ids);
				$this->counts['products']++;
			}
		}
	}

	/** A course's software's, every one of them: *Visualisieren mit SketchUp* takes SketchUp's and V-Ray's. */
	private function courses(): void
	{
		foreach (Course::query()->with(['categories', 'software.categories'])->get() as $course) {
			if ($course->categories->isNotEmpty()) {
				continue;
			}

			$ids = $course->software->flatMap(fn (Software $software) => $software->categories->modelKeys())->unique()->values()->all();

			if ($ids !== []) {
				$course->categories()->attach($ids);
				$this->counts['courses']++;
			}
		}
	}

	/**
	 * @param  list<string>  $titles
	 * @param  array<string, int>  $categories
	 * @return list<int>
	 */
	private function ids(array $titles, array $categories): array
	{
		return array_values(array_map(function (string $title) use ($categories): int {
			return $categories[$title] ?? throw new \RuntimeException("«{$title}» is not one of the file's categories.");
		}, $titles));
	}

	/** What is on the site and has none: VIAK's to set. */
	private function report(): void
	{
		$lists = [
			'Angebote' => Software::query()->onSite()->whereDoesntHave('categories')->get(),
			'courses' => Course::query()->published()->whereDoesntHave('categories')->get(),
			'licence products' => LicenceProduct::query()->listed()->whereDoesntHave('categories')->get(),
		];

		foreach ($lists as $what => $models) {
			/** @var Collection<int, Course|Software|LicenceProduct> $models */
			if ($models->isEmpty()) {
				continue;
			}

			$this->components->warn("{$models->count()} {$what} on the site without a category:");

			foreach ($models as $model) {
				$this->line('  '.$model->getTranslation('title', 'de'));
			}
		}
	}
}
