<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\EventState;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\EventRowResource;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A course date's own page on the dashboard ([[07-dashboard]], step 7) —
 * legacy's `course/event/Show.vue`, built so far as far as attendance needs:
 * the date, and its participants with the tick that decides who gets the
 * participation confirmation (Marcel, 2026-09-29).
 */
class EventPageController extends Controller
{
	public function show(Event $event): JsonResponse
	{
		$event->load(['course', 'dates', 'location', 'experts'])->loadCount([
			'bookings' => fn ($query) => $query->active(),
			'bookings as rentals_taken_count' => fn ($query) => $query->active()->where('has_rental', true),
		]);

		$participants = $event->bookings()->active()->with('user')->get()
			->sortBy(fn (Booking $booking) => mb_strtolower($booking->user->last_name.' '.$booking->user->first_name))
			->values()
			->map(fn (Booking $booking) => [
				'uuid' => $booking->uuid,
				'name' => $booking->user->name,
				'city' => $booking->user->city,
				// Legacy's fallback: the person's firm, else the one they bill to.
				'company' => $booking->user->company ?: ($booking->invoice_address['company'] ?? null),
				'email' => $booking->user->email,
				'has_rental' => $booking->has_rental,
				'participated' => $booking->hasParticipated(),
			]);

		return response()->json(['data' => [
			'event' => new EventRowResource($event),
			'course' => [
				'uuid' => $event->course->uuid,
				'number' => $event->course->number,
				'title' => $event->course->getTranslation('title', 'de'),
			],
			'participants' => $participants,
		]]);
	}

	/**
	 * Ticked as attended, or not. Not once the date is closed: the
	 * confirmations went out then, to the seats ticked at the time.
	 */
	public function participation(Request $request, Booking $booking): JsonResponse
	{
		$participated = $request->validate(['participated' => ['required', 'boolean']])['participated'];

		abort_if($booking->isCancelled(), 422, 'Diese Buchung ist annulliert.');
		abort_if(in_array($booking->event->state, [EventState::Closed, EventState::Cancelled], true), 422, 'Diese Veranstaltung ist abgeschlossen oder abgesagt.');

		$booking->forceFill(['participated_at' => $participated ? ($booking->participated_at ?? now()) : null])->save();

		return response()->json(['data' => ['uuid' => $booking->uuid, 'participated' => $booking->hasParticipated()]]);
	}
}
