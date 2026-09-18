<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
		}

		// Never a route parameter the controllers have to accept and ignore.
		$request->route()?->forgetParameter('locale');

		return $next($request);
	}
}
