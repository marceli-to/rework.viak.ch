<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Software;
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
	 * Resolved by the locale's slug rather than a uuid — the public URLs are
	 * indexed and printed on invoices, so they stay human-readable.
	 */
	public function show(string $slug): View
	{
		$course = Course::query()
			->published()
			->where('slug->'.app()->getLocale(), $slug)
			->with([
				'software', 'categories', 'levels',
				'events' => fn ($query) => $query->published()->active()->upcoming()->with(['dates', 'location']),
			])
			->firstOrFail();

		return view('site.courses.show', ['course' => $course]);
	}
}
