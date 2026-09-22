<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the locale out of the URL prefix ([[00-foundation]]).
 *
 * The rework does its own rather than carrying legacy's
 * `chinleung/laravel-multilingual-routes`: with one locale in scope, the package
 * is 900 lines to prepend a string, and its route-name scheme
 * (`de.page.course`) is what made legacy's `web.php` list every route twice.
 */
class SetLocaleFromUrl
{
	public function handle(Request $request, Closure $next): Response
	{
		$locale = $request->route('locale');

		if (is_string($locale) && in_array($locale, config('site.locales'), true)) {
			app()->setLocale($locale);

			/*
			 * So `route('de.student.profile')` resolves without being handed the
			 * locale it is already inside.
			 *
			 * Every route in the prefixed group takes `{locale}`, and the line
			 * below then takes it back off the request — which is right for the
			 * controllers and leaves `route()` with a required parameter and no
			 * value for it. Until the portal there was nothing generating these
			 * URLs by name (the checkout goes through [[SiteUrl]]), so the first
			 * `route()` call inside the group was also the first failure:
			 * *Missing parameter: locale*.
			 *
			 * A URL default rather than a `SiteUrl` method per route: these are
			 * form actions, and a form action is the route it posts to.
			 */
			URL::defaults(['locale' => $locale]);
		}

		// Never a route parameter the controllers have to accept and ignore.
		$request->route()?->forgetParameter('locale');

		return $next($request);
	}
}
