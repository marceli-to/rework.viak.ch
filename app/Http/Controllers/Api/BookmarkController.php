<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Saved courses ([[06-bookings]]).
 *
 * 17 rows in three years, so this is the whole feature: a pivot, two routes and
 * a star on a course card. No Action layer — there is no business rule here to
 * name — and no place in the dashboard's navigation.
 */
class BookmarkController extends Controller
{
	public function index(Request $request): AnonymousResourceCollection
	{
		return EventResource::collection(
			$request->user()->bookmarks()->with(['course', 'dates', 'location'])->get()
		);
	}

	public function store(Request $request, Event $event): JsonResponse
	{
		$request->user()->bookmarks()->syncWithoutDetaching([$event->id]);

		return response()->json(status: 204);
	}

	public function destroy(Request $request, Event $event): JsonResponse
	{
		$request->user()->forgetBookmark($event);

		return response()->json(status: 204);
	}
}
