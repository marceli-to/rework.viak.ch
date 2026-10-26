<?php

declare(strict_types=1);

namespace App\Support\Search;

/**
 * A record the site search finds ([[15-search]]): saving it rewrites its rows
 * in `search_entries`, deleting it removes them. What goes in, and whether a
 * record is on the site at all, is [[SearchDocument]]'s to say.
 *
 * A record whose findability depends on another (a software is on the site
 * only with a listed licence) is re-indexed when that one is saved too, from
 * [[SearchIndex::syncRelated]]; `search:rebuild` mends anything that slips.
 */
trait IndexedForSearch
{
	public static function bootIndexedForSearch(): void
	{
		static::saved(fn (self $record) => app(SearchIndex::class)->syncRelated($record));
		static::deleted(fn (self $record) => app(SearchIndex::class)->syncRelated($record));

		if (method_exists(static::class, 'restored')) {
			static::restored(fn (self $record) => app(SearchIndex::class)->syncRelated($record));
		}
	}
}
