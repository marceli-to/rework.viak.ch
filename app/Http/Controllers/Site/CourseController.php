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
	/**
	 * **The whole catalogue is rendered; the query string decides what is
	 * hidden.** It used to be a `whereHas` that dropped the rows, which meant
	 * every change of filter was a navigation — and on a phone, where the filter
	 * is a full-screen panel, a navigation closes the panel you are still using.
	 *
	 * So the filtering moved one step later: the server says which courses match
	 * and the view marks the rest `hidden`, which Alpine then takes over
	 * ([[09-public-site]]). The page is still filtered without JavaScript, a
	 * shared link still lands on the same view, and a crawler now sees all of
	 * `/de/kurse` instead of a slice of it.
	 *
	 * The cost is loading 32 rows where a filtered request loaded fewer — which
	 * is what the unfiltered page, by far the common one, already did. It stops
	 * being the right trade the day this list needs pagination.
	 */
	public function index(Request $request): View
	{
		$activeSoftware = $request->string('software')->value() ?: null;

		$courses = Course::query()
			->published()
			->with([
				'software',
				// The card shows a category, the next date, the expert teaching
				// it and the fee — all eager-loaded, or the grid asks per card.
				'categories',
				'media',
				'events' => fn ($query) => $query->published()->active()->upcoming()->with('experts'),
			])
			->ordered()
			->get();

		// One definition of a match, because the server and the browser have to
		// agree on it — `course-filter.js` applies the same rule to the same
		// uuids, read off `data-facets`.
		$matching = $courses
			->when($activeSoftware, fn ($all, $uuid) => $all->filter(
				fn (Course $course) => $course->software->contains('uuid', $uuid)
			))
			->pluck('uuid')
			->all();

		return view('site.courses.index', [
			'courses' => $courses,
			'matching' => $matching,
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
