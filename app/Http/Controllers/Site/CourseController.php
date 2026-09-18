<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Software;
use App\Support\SiteUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public course pages. Server-rendered rather than fetched by the SPA —
 * these are the pages that need to be indexable.
 */
class CourseController extends Controller
{
	public function index(Request $request): View
	{
		$activeSoftware = $request->string('software')->value() ?: null;

		$courses = Course::query()
			->published()
			->with([
				'software',
				// Eager-loaded, or the grid asks for an image per card.
				'media',
				'events' => fn ($query) => $query->published()->active()->upcoming(),
			])
			->when($activeSoftware, fn ($query, $uuid) => $query->whereHas(
				'software',
				fn ($software) => $software->where('uuid', $uuid)
			))
			->ordered()
			->get();

		return view('site.courses.index', [
			'courses' => $courses,
			'software' => Software::published()->ordered()->get(),
			'activeSoftware' => $activeSoftware,
		]);
	}

	/**
	 * Resolved by the locale's slug rather than a uuid.
	 *
	 * Legacy's route was `/de/kurs/{slug}/{uuid}` and the **uuid** is what
	 * resolved — the slug was decorative, so `/de/kurs/anything/{uuid}` renders
	 * the course on the live site today. The rework resolves by slug and 301s
	 * the uuid form; see `redirectLegacy()` below.
	 */
	public function show(string $slug): View
	{
		$course = Course::query()
			->published()
			->where('slug->'.app()->getLocale(), $slug)
			->with([
				'software', 'categories', 'levels', 'media',
				'events' => fn ($query) => $query->published()->active()->upcoming()->with(['dates', 'location']),
			])
			->firstOrFail();

		return view('site.courses.show', ['course' => $course]);
	}

	/**
	 * The indexed legacy URL, 301'd to the slug form.
	 *
	 * Resolves by **uuid**, exactly as legacy did, because that is the half of
	 * the old URL that identifies anything — and because a slug can be edited
	 * while the uuid cannot. `01-schema.md` carries legacy uuids across rather
	 * than regenerating them, precisely so links like this keep working.
	 *
	 * About 60 URLs across the site depend on this, so it is small; it is also
	 * the difference between keeping three years of ranking and starting again.
	 */
	public function redirectLegacy(string $slug, string $uuid): RedirectResponse
	{
		$course = Course::query()->published()->where('uuid', $uuid)->firstOrFail();

		return redirect(
			SiteUrl::course($course->getTranslation('slug', app()->getLocale())),
			301,
		);
	}
}
