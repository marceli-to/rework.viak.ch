<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Events\CreateEvent;
use App\Actions\Events\SetEventState;
use App\Actions\Events\UpdateEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveEventRequest;
use App\Http\Requests\Events\SetEventStateRequest;
use App\Http\Resources\Admin\EventFormResource;
use App\Http\Resources\Admin\EventRowResource;
use App\Models\Course;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

/**
 * A course date, for the admin ([[07-dashboard]]). Listing happens through the
 * catalogue ([[Admin\CourseController]]); this is its form and what acts on
 * one.
 */
class EventController extends Controller
{
	public function show(Event $event): EventFormResource
	{
		return new EventFormResource($event->load(['course', 'dates', 'location', 'experts']));
	}

	public function store(SaveEventRequest $request, Course $course, CreateEvent $create): JsonResponse
	{
		$event = $create->execute($course, $request->eventAttributes(), $request->dates(), $request->expertIds());

		return (new EventFormResource($event->load(['course', 'dates', 'location', 'experts'])))
			->response()
			->setStatusCode(201);
	}

	public function update(SaveEventRequest $request, Event $event, UpdateEvent $update): EventFormResource
	{
		$event = $update->execute($event, $request->eventAttributes(), $request->dates(), $request->expertIds());

		return new EventFormResource($event->load(['course', 'dates', 'location', 'experts']));
	}

	public function setState(SetEventStateRequest $request, Event $event, SetEventState $setState): EventRowResource
	{
		$event = $setState->execute($event, $request->state());

		return new EventRowResource($event
			->load(['course', 'dates', 'location', 'experts'])
			->loadCount([
				'bookings' => fn ($query) => $query->active(),
				'bookings as rentals_taken_count' => fn ($query) => $query->active()->where('has_rental', true),
			]));
	}

	/** Only without bookings, as legacy had it; cancelled ones count. */
	public function destroy(Event $event): JsonResponse
	{
		if ($event->bookings()->exists()) {
			return response()->json([
				'message' => 'Dieses Kursdatum hat Buchungen und kann nicht gelöscht werden.',
			], 422);
		}

		$event->delete();

		return response()->json(status: 204);
	}
}
