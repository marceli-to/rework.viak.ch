<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale;
use Illuminate\Support\Str;

/**
 * Slugs are translatable: one per locale, stored in the same JSON column shape
 * spatie/laravel-translatable uses for every other translated field.
 */
final class Slug
{
	/**
	 * @param  array<string, string|null>|string  $titles
	 * @return array<string, string>
	 */
	public static function forTitles(array|string $titles): array
	{
		$titles = is_string($titles) ? [Locale::De->value => $titles] : $titles;
		$slugs = [];

		foreach (Locale::values() as $locale) {
			$source = $titles[$locale] ?? reset($titles);

			if (filled($source)) {
				$slugs[$locale] = Str::slug((string) $source);
			}
		}

		return $slugs;
	}
}
