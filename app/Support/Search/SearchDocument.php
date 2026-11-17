<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Enums\Role;
use App\Models\Course;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Project;
use App\Models\Software;
use App\Models\User;
use App\Support\SiteUrl;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * What the site search knows about one record, in one locale ([[15-search]]):
 * the type, the title, the text searched beside it, a lead for the result
 * list and the page it links to. **Null when the record is not on the site**,
 * by the same rule its page uses, so the search never finds a page that 404s.
 *
 * **As much as possible** (#56, Oliver 2026-11-17): courses, Angebote (every
 * text of their three pages), licences, Vorhaben, experts, and the fixed
 * pages ([[SearchDocument::pages]]). A licence links to its block on its
 * Angebot's licence page.
 */
class SearchDocument
{
	/** @var list<class-string<Model>> */
	public const MODELS = [Course::class, Software::class, LicenceProduct::class, Project::class, User::class];

	public const LABELS = [
		'course' => 'Kurs', 'software' => 'Angebot', 'licence' => 'Lizenz', 'project' => 'Vorhaben',
		'expert' => 'Expert:in', 'page' => 'Seite',
	];

	/**
	 * **The fixed pages** (#56): their text is read off the page itself at
	 * `search:rebuild` ([[SearchDocument::pages]]), which every deploy runs,
	 * so it follows the copy. The lists (Alle Angebote, Kurse, Software) are
	 * tiles of what the search finds anyway, so they carry a lead and a few
	 * words, not their tiles' text.
	 *
	 * @var array<int, array{title: string, url: string, lead: string, words?: string, read?: bool}>
	 */
	private const PAGES = [
		1 => ['title' => 'Über uns', 'url' => 'about', 'lead' => 'Die Visualisierungs-Akademie seit 2003, ihre Expert:innen und das Team.', 'read' => true],
		2 => ['title' => 'Firmenschulung', 'url' => 'training', 'lead' => 'Individualschulungen für Firmen und Einzelpersonen, massgeschneidert, bei uns oder vor Ort.', 'words' => 'Individualschulung Firmenkurs Inhouse Coaching', 'read' => true],
		3 => ['title' => 'Kontakt', 'url' => 'contact', 'lead' => 'Adresse, Anreise, Kontaktformular und Impressum.', 'words' => 'Adresse Telefon E-Mail Anreise Impressum AGB', 'read' => true],
		4 => ['title' => 'Alle Angebote', 'url' => 'offers', 'lead' => 'Kurse und Software nach Bereichen.', 'words' => 'Angebote Übersicht Bereiche Kategorien'],
		5 => ['title' => 'Kurse', 'url' => 'courses', 'lead' => 'Alle Kurse und ihre Daten, mit Filter.', 'words' => 'Kurse Schulungen Kursdaten Termine Weiterbildung'],
		6 => ['title' => 'Software', 'url' => 'softwareIndex', 'lead' => 'Alle Lizenzen im Shop, mit Filter.', 'words' => 'Software Lizenzen Shop kaufen'],
	];

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
			$record instanceof User => self::expert($record, $locale),
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

		// Every text of its three pages: the Angebot's, «Was ist …?», «Lizenzen».
		return self::document('software', $software, $locale, SiteUrl::software($slug, $locale),
			fields: [
				'subtitle', 'short_description', 'audiences', 'intro', 'courses_intro', 'licences_intro', 'quote',
				'full_description', 'information', 'licences_lead', 'requirements', 'licences_quote', 'seo_tags',
			],
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

		return self::document('licence', $product, $locale, SiteUrl::licence($product, $locale),
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

	/** An expert's page, while it is listed ([[User::scopePubliclyListedExperts]]): name, role, text. */
	private static function expert(User $user, string $locale): ?array
	{
		$listed = User::query()->publiclyListedExperts()->where('users.id', $user->getKey())->with('expertProfile')->first();

		if ($listed === null) {
			return null;
		}

		$profile = $listed->expertProfile;
		$name = trim($listed->first_name.' '.$listed->last_name);

		if ($name === '') {
			return null;
		}

		return [
			'type' => 'expert',
			'title' => Str::limit($name, 250, ''),
			'body' => collect([$profile?->title, self::plain($profile?->description)])->filter()->implode("\n"),
			'lead' => $profile?->title ? Str::limit(self::plain($profile->title), 280) : null,
			'url' => SiteUrl::expert($listed, $locale),
		];
	}

	/**
	 * The fixed pages' rows (`PAGES`), keyed by their number. A page that is
	 * `read` is fetched through the app, as a visitor would, and its `<main>`
	 * taken as text (no forms, scripts or pictures); one that does not answer
	 * keeps its lead and words.
	 *
	 * @return array<int, array{type: string, title: string, body: string, lead: string, url: string}>
	 */
	public static function pages(string $locale): array
	{
		return collect(self::PAGES)->map(function (array $page) use ($locale) {
			$url = SiteUrl::{$page['url']}($locale);

			return [
				'type' => 'page',
				'title' => $page['title'],
				'body' => trim(($page['words'] ?? '').' '.(($page['read'] ?? false) ? self::read($url) : '')),
				'lead' => $page['lead'],
				'url' => $url,
			];
		})->all();
	}

	/** A page's `<main>`, as plain text; empty when it does not answer 200. */
	private static function read(string $url): string
	{
		$response = app(Kernel::class)->handle(Request::create($url));

		if ($response->getStatusCode() !== 200) {
			return '';
		}

		$html = (string) $response->getContent();
		$main = preg_match('/<main\b.*<\/main>/s', $html, $match) ? $match[0] : '';
		$main = (string) preg_replace('/<(script|style|svg|form|template)\b[^>]*>.*?<\/\1>/si', ' ', $main);

		return self::plain($main);
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
