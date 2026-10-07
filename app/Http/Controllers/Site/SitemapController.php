<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Project;
use App\Models\User;
use App\Support\SiteUrl;
use Illuminate\Http\Response;

/**
 * `/sitemap.xml` (`Todo.md`, *SEO*). Legacy has none: its `robots.txt` allows
 * everything and names nothing.
 *
 * Every page a guest can reach, per locale, on the canonical host, and nothing
 * else: the fixed pages, each **published** Vorhaben and course by its slug,
 * each **listed** expert. The queries are the pages' own (`published()`,
 * `publiclyListedExperts()`), so the sitemap cannot name a page that 404s.
 * Legacy's uuid course URLs are left out: they 301 to these.
 *
 * **No `lastmod`.** A course page changes when its dates do, not when the row
 * does, and the ported rows carry the port's timestamps. A wrong date is worse
 * than none, which search engines read as *unknown*.
 */
class SitemapController extends Controller
{
	public function __invoke(): Response
	{
		$urls = collect(config('site.locales'))->flatMap(fn (string $locale) => [
			SiteUrl::home($locale),
			...Project::query()->published()->ordered()->get()
				->map(fn (Project $project) => $project->getTranslation('slug', $locale, false))
				->filter()
				->map(fn (string $slug) => SiteUrl::project($slug, $locale)),
			SiteUrl::courses($locale),
			...Course::query()->published()->orderBy('order')->get()
				->map(fn (Course $course) => $course->getTranslation('slug', $locale, false))
				->filter()
				->map(fn (string $slug) => SiteUrl::course($slug, $locale)),
			SiteUrl::about($locale),
			...User::query()->publiclyListedExperts()->get()
				->map(fn (User $expert) => SiteUrl::expert($expert, $locale)),
			SiteUrl::contact($locale),
			SiteUrl::training($locale),
		])->map(fn (string $path) => SiteUrl::canonical($path));

		return response()
			->view('site.sitemap', ['urls' => $urls])
			->header('Content-Type', 'application/xml; charset=UTF-8');
	}
}
