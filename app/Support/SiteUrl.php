<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Builds public URLs with the locale prefix and the locale's own path segments
 * ([[00-foundation]]).
 *
 * Exists so no view hardcodes `/de/kurs/…`. Turning EN on then means adding a
 * locale to `config/site.php`, not finding every link.
 */
final class SiteUrl
{
	public static function segment(string $key, ?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return config("site.segments.{$locale}.{$key}", $key);
	}

	/** The course list — `/de/kurse`. */
	public static function courses(?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('courses', $locale);
	}

	/** One course — `/de/kurs/{slug}`. */
	public static function course(string $slug, ?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('course', $locale).'/'.$slug;
	}

	public static function home(?string $locale = null): string
	{
		return '/'.($locale ?? app()->getLocale());
	}

	/** The absolute form, on the host the site is actually indexed under. */
	public static function canonical(string $path): string
	{
		return 'https://'.config('site.canonical_host').$path;
	}
}
