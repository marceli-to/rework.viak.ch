<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CourseRowResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The dashboard's *Kurse* screen ([[07-dashboard]]) — legacy's one screen with
 * two modes: every upcoming date chronologically, or the courses as a sortable
 * accordion with their dates inside.
 */
class CourseController extends Controller
{
	/**
	 * **The whole catalogue in one response, unpaginated** — both modes are
	 * views of it, and the accordion can only be dragged into order when every
	 * course is on the page. It is bounded by the catalogue, not by time: 41
	 * courses and about 30 upcoming dates today. The server-side search and
	 * pagination rule is for the lists that grow — people and invoices.
	 *
	 * Each course carries its **upcoming** dates, cancelled ones left out, as
	 * legacy's screen shows them: its chronological mode opens on the next date,
	 * not on 2022. Past dates are reached from the course, as they are there.
	 */
	public function index(): AnonymousResourceCollection
	{
		$courses = Course::query()
			->with(['events' => fn ($query) => $query
				->active()
				->upcoming()
				->with(['dates', 'location', 'experts'])
				->withCount([
					'bookings' => fn ($query) => $query->active(),
					'bookings as rentals_taken_count' => fn ($query) => $query->active()->where('has_rental', true),
				]),
			])
			->ordered()
			->get();

		return CourseRowResource::collection($courses);
	}

	/**
	 * The accordion dropped into a new order. The list arrives whole, so the
	 * positions are simply 1…n — which also repairs the ties legacy's order
	 * column is full of.
	 */
	public function order(Request $request): JsonResponse
	{
		$uuids = $request->validate([
			'courses' => ['required', 'array'],
			'courses.*' => ['string', Rule::exists('courses', 'uuid')],
		])['courses'];

		DB::transaction(function () use ($uuids): void {
			foreach (array_values($uuids) as $position => $uuid) {
				Course::query()->where('uuid', $uuid)->update(['order' => $position + 1]);
			}
		});

		return response()->json(status: 204);
	}
}
