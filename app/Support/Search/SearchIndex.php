<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The site search ([[15-search]]): keeping `search_entries` in step with the
 * records, and asking it.
 *
 * **MySQL FULLTEXT, no search service.** Some hundred records do not need
 * Meilisearch. The query is boolean mode, every word required, each a
 * prefix (*rhin* finds Rhino).
 *
 * **Words under three characters** (*3D*, *KI*, the *V* of *V-Ray*) are below
 * InnoDB's `innodb_ft_min_token_size` and never reach the index, so they are
 * matched with `REGEXP` at the start of a word in the title or body instead.
 * That needs nothing set on the server. A query of short words only is
 * therefore a scan of the table, which at a few hundred rows is nothing.
 *
 * **Order**: how many of the words start a word in the title; then software,
 * course, Vorhaben, licence (a licence links to its software's page, so the
 * page comes first); then the full-text relevance, a title hit weighing three
 * times a body hit.
 */
class SearchIndex
{
	public const MIN_TOKEN = 3;

	/** Rewrite one record's rows, or remove them if it is not on the site. */
	public function sync(Model $record): void
	{
		foreach ((array) config('site.locales') as $locale) {
			$key = ['searchable_type' => $record->getMorphClass(), 'searchable_id' => $record->getKey(), 'locale' => $locale];
			$document = SearchDocument::for($record, $locale);

			$document === null
				? DB::table('search_entries')->where($key)->delete()
				: DB::table('search_entries')->updateOrInsert($key, [...$document, 'updated_at' => now(), 'created_at' => now()]);
		}
	}

	/**
	 * The record, and those whose place on the site it decides: a licence
	 * product's software (on the site only with a listed licence), a
	 * software's products (found only while it is), a variant's product.
	 */
	public function syncRelated(Model $record): void
	{
		$records = match (true) {
			$record instanceof LicenceVariant => [$record->product, $record->product?->software],
			$record instanceof LicenceProduct => [$record, $record->software],
			$record instanceof Software => [$record, ...$record->products()->get()],
			default => [$record],
		};

		foreach (array_filter($records) as $related) {
			if (in_array($related::class, SearchDocument::MODELS, true)) {
				$this->sync($related->withoutRelations());
			}
		}
	}

	/** Every row from scratch ([[RebuildSearchIndex]]). Returns how many were written. */
	public function rebuild(): int
	{
		DB::table('search_entries')->delete();

		foreach (SearchDocument::MODELS as $model) {
			$model::query()->lazyById(100)->each(fn (Model $record) => $this->sync($record));
		}

		return DB::table('search_entries')->count();
	}

	/**
	 * The hits for a query, best first.
	 *
	 * @return Collection<int, object{type: string, label: string, title: string, lead: ?string, url: string}>
	 */
	public function search(string $query, string $locale, int $limit = 20): Collection
	{
		$words = self::words($query);

		if ($words === []) {
			return collect();
		}

		[$long, $short] = collect($words)->partition(fn (string $word) => mb_strlen($word) >= self::MIN_TOKEN)->map->values();

		$against = $long->map(fn (string $word) => "+{$word}*")->implode(' ');

		// How many of the words start a word in the title: *Lumion Pro* has
		// both, the Lumion course only one, however often its text says Lumion.
		$inTitle = $words === [] ? '0' : implode(' + ', array_fill(0, count($words), '(title REGEXP ?)'));
		$starts = array_map(fn (string $word) => '\\b'.$word, $words);

		$rows = DB::table('search_entries')
			->select(['type', 'title', 'lead', 'url'])
			->where('locale', $locale)
			->when($against !== '', fn ($rows) => $rows->whereFullText(['title', 'body'], $against, ['mode' => 'boolean']))
			// A short word starts a word somewhere: *KI* is *KI-Einstieg*, not *Skizze*.
			->when($short->isNotEmpty(), function ($rows) use ($short) {
				foreach ($short as $word) {
					$rows->where(fn ($either) => $either->where('title', 'regexp', '\\b'.$word)->orWhere('body', 'regexp', '\\b'.$word));
				}

				return $rows;
			})
			->orderByRaw("({$inTitle}) DESC", $starts)
			// Level on the title: the Angebot before its courses and its licence.
			->orderByRaw("FIELD(type, 'software', 'course', 'project', 'licence')")
			->when($against !== '', fn ($rows) => $rows
				->orderByRaw('(MATCH (title) AGAINST (? IN BOOLEAN MODE) * 3 + MATCH (title, body) AGAINST (? IN BOOLEAN MODE)) DESC', [$against, $against]))
			->orderBy('title')
			->limit($limit)
			->get();

		return $rows->map(fn (object $row) => (object) [...(array) $row, 'label' => SearchDocument::LABELS[$row->type] ?? $row->type]);
	}

	/**
	 * The query as words: letters and digits only, so nothing reaches MySQL's
	 * boolean syntax (`+ - < > ( ) ~ * " @`). At most eight words.
	 *
	 * @return list<string>
	 */
	public static function words(string $query): array
	{
		$words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];

		return array_slice(array_values(array_unique($words)), 0, 8);
	}
}
