<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Events\CreateEvent;
use App\Actions\Events\SetEventState;
use App\Actions\Events\UpdateEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\SetEventStateRequest;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
	public function index(Request $request): AnonymousResourceCollection
	{
		$events = Event::query()
			->when(! $request->user()?->isAdmin(), fn ($query) => $query->published()->active())
			->when($request->boolean('past'), fn ($query) => $query->past(), fn ($query) => $query->upcoming())
			->with(['course', 'dates', 'location'])
			->get();

		return EventResource::collection($events);
	}

	public function show(Event $event): EventResource
	{
		$this->authorize('view', $event);

		return new EventResource($event->load(['course', 'dates', 'location', 'experts']));
	}

	public function store(StoreEventRequest $request, CreateEvent $create): JsonResponse
	{
		$event = $create->execute(
			course: $request->resolvedCourse(),
			attributes: $request->eventAttributes(),
			dates: $request->dates(),
			expertIds: $request->expertIds(),
		);

		return (new EventResource($event->load(['course', 'dates', 'location', 'experts'])))
			->response()
			->setStatusCode(201);
	}

	public function update(StoreEventRequest $request, Event $event, UpdateEvent $update): EventResource
	{
		$event = $update->execute(
			event: $event,
			attributes: $request->eventAttributes(),
			dates: $request->dates(),
			expertIds: $request->expertIds(),
		);

		return new EventResource($event->load(['course', 'dates', 'location', 'experts']));
	}

	public function setState(SetEventStateRequest $request, Event $event, SetEventState $setState): EventResource
	{
		$event = $setState->execute($event, $request->state());

		return new EventResource($event->load(['course', 'dates', 'location']));
	}

	public function destroy(Event $event): JsonResponse
	{
		$this->authorize('delete', $event);

		$event->delete();

		return response()->json(status: 204);
	}
}
