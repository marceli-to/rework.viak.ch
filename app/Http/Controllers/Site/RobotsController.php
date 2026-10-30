<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * `/robots.txt`, a route since 2026-10-30 (was `public/robots.txt`).
 *
 * **Only production may be crawled.** A staging copy holds the whole site
 * and must not compete with it in search results, so everywhere else it
 * disallows everything. In production: everything allowed, as legacy, and
 * the sitemap named ([[SitemapController]]), on the canonical host.
 */
class RobotsController extends Controller
{
	public function __invoke(): Response
	{
		$body = app()->isProduction()
			? "User-agent: *\nDisallow:\n\nSitemap: https://".config('site.canonical_host')."/sitemap.xml\n"
			: "User-agent: *\nDisallow: /\n";

		return response($body)->header('Content-Type', 'text/plain; charset=UTF-8');
	}
}
