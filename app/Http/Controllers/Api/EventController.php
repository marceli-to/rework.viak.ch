<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Course dates, read — the site and the portals. Writing them is the
 * dashboard's ([[Admin\EventController]]).
 */
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
}
