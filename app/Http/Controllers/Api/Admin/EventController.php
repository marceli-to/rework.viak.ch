<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Events\SetEventState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\SetEventStateRequest;
use App\Http\Resources\Admin\EventRowResource;
use App\Models\Event;

/**
 * A course date, for the admin ([[07-dashboard]]). Listing happens through the
 * catalogue ([[Admin\CourseController]]); this is what acts on one.
 */
class EventController extends Controller
{
	public function setState(SetEventStateRequest $request, Event $event, SetEventState $setState): EventRowResource
	{
		$event = $setState->execute($event, $request->state());

		return new EventRowResource($event
			->load(['course', 'dates', 'location', 'experts'])
			->loadCount([
				'bookings' => fn ($query) => $query->active(),
				'bookings as rentals_count' => fn ($query) => $query->active()->where('has_rental', true),
			]));
	}
}
