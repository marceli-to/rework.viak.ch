<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\Site\CourseController;
use App\Http\Middleware\SetLocaleFromUrl;
use Illuminate\Support\Facades\Route;

/*
 * The root redirects to the language root ([[00-foundation]]).
 *
 * Legacy serves **both** `/` and `/de` with 200 and no canonical tag, which is
 * duplicate content on the live site today — verified against production on
 * 2026-09-18, identical `<title>` on each.
 *
 * The scope note said to fix that with a canonical tag. A 301 is better and is
 * what this does: a canonical is a hint, a redirect is a directive, and `/` has
 * no content of its own to keep. It costs one round trip on the most-linked URL
 * and consolidates the two properly.
 */
Route::redirect('/', '/'.config('app.locale'), 301);

/*
 * On-demand image rendering ([[08-accounts]]). Parameters are clamped to the
 * sizes and formats `<x-media.image>` would have produced, so the resizer cannot
 * be used to fill the cache disk.
 */
Route::get('/img/{path}', [ImageController::class, 'show'])
	->where('path', '.*')
	->name('image');

/*
 * Everything the public sees, under its locale prefix.
 *
 * The segments come from `config/site.php` rather than being written here, so
 * `/en/course/{slug}` exists the moment a locale is added — and so nothing in a
 * view has to know that the list is `kurse` and the detail is `kurs`. That
 * plural/singular split is legacy's, it is what is indexed, and it is not ours
 * to tidy ([[SiteUrl]]).
 */
Route::prefix('{locale}')
	->whereIn('locale', config('site.locales'))
	->middleware(SetLocaleFromUrl::class)
	->group(function (): void {
		foreach (config('site.locales') as $locale) {
			$segments = config("site.segments.{$locale}");

			Route::view('/', 'site.home')->name("{$locale}.home");

			Route::get($segments['courses'], [CourseController::class, 'index'])
				->name("{$locale}.courses.index");

			Route::get($segments['course'].'/{slug}', [CourseController::class, 'show'])
				->name("{$locale}.courses.show");

			/*
			 * The legacy detail URL carried a slug *and* a uuid, and the uuid is
			 * what resolved — so `/de/kurs/anything/{uuid}` renders the course
			 * today. 301 to the slug form, which passes essentially full
			 * ranking and retires the wart. `01-schema.md` carries legacy uuids
			 * across rather than regenerating them, which is what makes this
			 * lookup possible at all.
			 */
			Route::get($segments['course'].'/{slug}/{uuid}', [CourseController::class, 'redirectLegacy'])
				->whereUuid('uuid')
				->name("{$locale}.courses.legacy");
		}
	});

/*
 * Generated PDFs, behind the session guard and a policy ([[08-accounts]]).
 * Legacy served these straight off the public disk.
 */
Route::get('/dokumente/{document}', [DocumentController::class, 'show'])
	->middleware('auth')
	->name('documents.show');

// SPA shell — the dashboard router takes over client-side.
Route::view('/dashboard/{any?}', 'components.layout.app')
	->where('any', '.*')
	->name('dashboard');
