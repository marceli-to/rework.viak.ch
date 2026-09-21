<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Support\CourseFilter;
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
		$courses = Course::query()
			->published()
			->with([
				// The card shows a category, the next date, the expert teaching
				// it and the fee — all eager-loaded, or the grid asks per card.
				'categories',
				'media',
				'events' => fn ($query) => $query->published()->active()->upcoming()->with('experts'),
				// The rest are what the filter offers and matches on; loading
				// them here is also what lets the panel offer only the terms
				// some course on the page actually carries.
				'software', 'levels', 'languages', 'tags',
			])
			->ordered()
			->get();

		return view('site.courses.index', [
			'courses' => $courses,
			'filter' => CourseFilter::for($courses, $request->query()),
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
	public function show(Request $request, string $slug): View
	{
		$course = Course::query()
			->published()
			->where('slug->'.app()->getLocale(), $slug)
			->with([
				'software', 'categories', 'levels', 'media',
				'videos' => fn ($query) => $query->published()->ordered(),
				'events' => fn ($query) => $query->published()->active()->upcoming()
					->with(['dates', 'location', 'experts']),
			])
			->firstOrFail();

		$user = $request->user();

		return view('site.courses.show', [
			'course' => $course,
			'browse' => $this->browse($course),
			/*
			 * Two flat id lists rather than a query per card. Both are empty for
			 * a guest, which is the common case and costs nothing.
			 */
			'bookmarked' => $user?->bookmarks()->pluck('events.id')->all() ?? [],
			'booked' => $user?->bookings()->active()->pluck('event_id')->all() ?? [],
		]);
	}

	/**
	 * The previous and next course in the catalogue, for the pair of arrows at
	 * the foot of the page.
	 *
	 * **It wraps.** Legacy's `getBrowse()` sends the first course's *previous*
	 * to the last one and the last course's *next* to the first, rather than
	 * hiding an arrow, so the pair is always two links. It returns nothing at
	 * all when there is only one course to browse.
	 *
	 * Ordered by the catalogue's own order, which is the same list `index()`
	 * renders — so the arrows walk the page the visitor came from.
	 *
	 * @return array{prev: Course, next: Course}|null
	 */
	private function browse(Course $course): ?array
	{
		$ids = Course::query()->published()->ordered()->pluck('id')->all();

		if (count($ids) <= 1) {
			return null;
		}

		$at = array_search($course->id, $ids, true);

		if ($at === false) {
			return null;
		}

		$courses = Course::query()
			->whereIn('id', [
				$ids[$at - 1] ?? end($ids),
				$ids[$at + 1] ?? $ids[0],
			])
			->get()
			->keyBy('id');

		return [
			'prev' => $courses[$ids[$at - 1] ?? end($ids)],
			'next' => $courses[$ids[$at + 1] ?? $ids[0]],
		];
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
