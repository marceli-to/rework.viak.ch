<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SiteUrl;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The Experten page and one expert's page — legacy's `ExpertController`,
 * rebuilt ([[09-public-site]]).
 */
class ExpertController extends Controller
{
	public function index(): View
	{
		return view('site.experts.index', [
			'experts' => User::query()
				->publiclyListedExperts()
				->with(['media', 'eventsAsExpert' => $this->teaching(...)])
				->get(),
		]);
	}

	/**
	 * Resolved by **uuid**, as legacy does; the slug is decorative and a stale
	 * one 301s to the current spelling ([[SiteUrl::expert]]).
	 */
	public function show(string $slug, string $uuid): View|RedirectResponse
	{
		$expert = User::query()
			->publiclyListedExperts()
			->where('users.uuid', $uuid)
			->with(['expertProfile', 'media', 'eventsAsExpert' => $this->teaching(...)])
			->firstOrFail();

		if ($slug !== SiteUrl::expertSlug($expert)) {
			return redirect(SiteUrl::expert($expert), 301);
		}

		return view('site.experts.show', ['expert' => $expert]);
	}

	/**
	 * The course dates behind the *Kurse* list on both pages — legacy's
	 * `User::getCourses()`, every event of theirs from today on.
	 *
	 * **Three filters legacy does not have**: published, not cancelled, and a
	 * published course. Legacy lists an expert's unpublished and cancelled
	 * dates too, so a course could appear here and 404 when clicked. On the
	 * 2026-09-11 data the filters change nothing — all ten experts list the same
	 * courses either way, checked on 2026-09-24 — so they cost no parity and
	 * close the gap before it opens.
	 *
	 * **Ordered by id, not by date.** Legacy reads the pivot without an order
	 * and gets the dates in the order they were entered, and the de-duplicated
	 * course list inherits it; ordering by date gives the same courses in a
	 * different order on seven of the ten experts. `upcoming()` is not used for
	 * that reason — it orders by date.
	 */
	private function teaching(BelongsToMany $query): void
	{
		$query
			->published()
			->active()
			->whereDate('events.date', '>=', today())
			->whereHas('course', fn ($course) => $course->published())
			->with('course')
			->orderBy('events.id');
	}
}
