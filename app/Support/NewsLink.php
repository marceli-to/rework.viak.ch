<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Course;
use App\Models\Software;

/**
 * **Where a news item's arrow leads** ([[News]]): a page of the site, picked
 * from a list rather than typed (the dashboard's rule: pick, don't type).
 * Stored as a key, `software:{uuid}`, `course:{uuid}` or `page:{name}`, and
 * turned into an address when the homepage is drawn, so a renamed Angebot
 * keeps its link and one that leaves the site loses the arrow instead of
 * leading to a 404.
 */
final class NewsLink
{
	/** The fixed pages, by the [[SiteUrl]] method that makes their address. */
	private const PAGES = [
		'offers' => 'Alle Angebote',
		'courses' => 'Kurse',
		'softwareIndex' => 'Software',
		'training' => 'Firmenschulung',
		'about' => 'Über uns',
		'contact' => 'Kontakt',
	];

	/**
	 * The choices, grouped as the select draws them: *Seiten*, *Angebote*,
	 * *Kurse*, each by name. Unpublished ones too, so a news item can be
	 * written before its page goes live; until then it has no arrow.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function options(): array
	{
		$byTitle = fn ($records) => $records
			->mapWithKeys(fn ($record) => [$record::class === Course::class ? 'course:'.$record->uuid : 'software:'.$record->uuid => (string) $record->getTranslation('title', 'de')])
			->sort(fn (string $a, string $b) => strnatcasecmp($a, $b))
			->all();

		return [
			'Seiten' => collect(self::PAGES)->mapWithKeys(fn (string $label, string $page) => ['page:'.$page => $label])->all(),
			'Angebote' => $byTitle(Software::query()->get()),
			'Kurse' => $byTitle(Course::query()->get()),
		];
	}

	/** The address for a key, or null when there is none or its page is not on the site. */
	public static function url(?string $key): ?string
	{
		[$kind, $id] = array_pad(explode(':', (string) $key, 2), 2, null);

		return match ($kind) {
			'page' => isset(self::PAGES[$id]) ? SiteUrl::{$id}() : null,
			'software' => ($software = Software::query()->onSite()->where('uuid', $id)->first())
				? SiteUrl::software($software->getTranslation('slug', app()->getLocale()))
				: null,
			'course' => ($course = Course::query()->published()->where('uuid', $id)->first())
				? SiteUrl::course($course->getTranslation('slug', app()->getLocale()))
				: null,
			default => null,
		};
	}
}
