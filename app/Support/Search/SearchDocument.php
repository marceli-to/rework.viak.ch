<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Models\Course;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Project;
use App\Models\Software;
use App\Support\SiteUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * What the site search knows about one record, in one locale ([[15-search]]):
 * the type, the title, the text searched beside it, a lead for the result
 * list and the page it links to. **Null when the record is not on the site**,
 * by the same rule its page uses, so the search never finds a page that 404s.
 *
 * Covered for now (#56): courses, software, licences and Vorhaben. A licence
 * has no page of its own; it links to its software's.
 */
class SearchDocument
{
	/** @var list<class-string<Model>> */
	public const MODELS = [Course::class, Software::class, LicenceProduct::class, Project::class];

	public const LABELS = ['course' => 'Kurs', 'software' => 'Software', 'licence' => 'Lizenz', 'project' => 'Vorhaben'];

	/** The result page's group headings, in its order ([[Site\SearchController]]). */
	public const GROUPS = ['software' => 'Software', 'course' => 'Kurse', 'project' => 'Vorhaben', 'licence' => 'Lizenzen'];

	/** @return array{type: string, title: string, body: string, lead: ?string, url: string}|null */
	public static function for(Model $record, string $locale): ?array
	{
		if (method_exists($record, 'trashed') && $record->trashed()) {
			return null;
		}

		return match (true) {
			$record instanceof Course => self::course($record, $locale),
			$record instanceof Software => self::software($record, $locale),
			$record instanceof LicenceProduct => self::licence($record, $locale),
			$record instanceof Project => self::project($record, $locale),
			default => null,
		};
	}

	private static function course(Course $course, string $locale): ?array
	{
		if (! $course->publish || ! ($slug = $course->getTranslation('slug', $locale, false))) {
			return null;
		}

		$course->loadMissing(['software', 'categories']);

		return self::document('course', $course, $locale, SiteUrl::course($slug, $locale),
			fields: ['subtitle', 'summary', 'short_description', 'full_description', 'seo_tags'],
			extra: [...self::titles($course->software, $locale), ...self::titles($course->categories, $locale)],
			lead: self::first($course, $locale, ['subtitle', 'summary', 'short_description']),
		);
	}

	private static function software(Software $software, string $locale): ?array
	{
		if (! Software::query()->onSite()->whereKey($software->getKey())->exists()
			|| ! ($slug = $software->getTranslation('slug', $locale, false))) {
			return null;
		}

		return self::document('software', $software, $locale, SiteUrl::software($slug, $locale),
			fields: ['subtitle', 'short_description', 'full_description', 'information', 'seo_tags'],
			lead: self::first($software, $locale, ['subtitle', 'short_description']),
		);
	}

	private static function licence(LicenceProduct $product, string $locale): ?array
	{
		$product->loadMissing(['software', 'manufacturer', 'variants']);

		$software = $product->software;

		if (! $product->isListed() || $software === null || SearchDocument::for($software, $locale) === null) {
			return null;
		}

		$variants = $product->variants->filter(fn (LicenceVariant $variant) => $variant->listed);

		return self::document('licence', $product, $locale, SiteUrl::software($software->getTranslation('slug', $locale), $locale),
			fields: ['description'],
			extra: [
				$software->getTranslation('title', $locale, false),
				$product->manufacturer?->getTranslation('title', $locale, false),
				...self::titles($variants, $locale),
			],
			lead: $software->getTranslation('title', $locale, false),
		);
	}

	private static function project(Project $project, string $locale): ?array
	{
		if (! $project->publish || ! ($slug = $project->getTranslation('slug', $locale, false))) {
			return null;
		}

		return self::document('project', $project, $locale, SiteUrl::project($slug, $locale),
			fields: ['teaser', 'lead', 'text', 'seo_tags'],
			lead: self::first($project, $locale, ['teaser', 'lead']),
		);
	}

	/**
	 * @param  list<string>  $fields
	 * @param  list<?string>  $extra
	 */
	private static function document(string $type, Model $record, string $locale, string $url, array $fields, array $extra = [], ?string $lead = null): ?array
	{
		$title = self::plain($record->getTranslation('title', $locale, false));

		if ($title === '') {
			return null;
		}

		$body = collect($fields)
			->map(fn (string $field) => self::plain($record->getTranslation($field, $locale, false)))
			->merge(array_map(self::plain(...), $extra))
			->filter()
			->implode("\n");

		return [
			'type' => $type,
			'title' => Str::limit($title, 250, ''),
			'body' => $body,
			'lead' => $lead === null ? null : Str::limit($lead, 280),
			'url' => parse_url($url, PHP_URL_PATH) ?: $url,
		];
	}

	/** The first of the fields that has any text, as plain text. */
	private static function first(Model $record, string $locale, array $fields): ?string
	{
		foreach ($fields as $field) {
			if (($text = self::plain($record->getTranslation($field, $locale, false))) !== '') {
				return $text;
			}
		}

		return null;
	}

	/** @return list<?string> */
	private static function titles(iterable $records, string $locale): array
	{
		return collect($records)->map(fn (Model $record) => $record->getTranslation('title', $locale, false))->all();
	}

	/** Rich text as a reader sees it: no tags, no entities, one space between words. */
	public static function plain(mixed $value): string
	{
		if (is_array($value)) {
			$value = implode(' ', array_filter($value, 'is_string'));
		}

		$text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], ' ', (string) $value)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

		return trim((string) preg_replace('/\s+/u', ' ', $text));
	}
}
