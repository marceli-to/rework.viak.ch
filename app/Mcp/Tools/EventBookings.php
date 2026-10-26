<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Booking;
use App\Models\Event;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * Who is booked on one Veranstaltung: the event page's participant list
 * ([[EventPageController]]) for an assistant ([[17-mcp]]). Found by its
 * number, as the office says it (*05-161126*), or by uuid.
 */
#[Description('The bookings of one Veranstaltung, found by its number (e.g. 05-161126) or uuid: participant name, company, e-mail, when booked, laptop rental, attendance. Cancelled bookings are counted, not listed.')]
#[IsReadOnly]
class EventBookings extends Tool
{
	public function handle(Request $request): Response
	{
		$input = $request->validate(['event' => ['required', 'string', 'max:64']]);

		$event = $this->find(trim($input['event']));

		if ($event === null) {
			return Response::error("Keine Veranstaltung {$input['event']} gefunden.");
		}

		$bookings = $event->bookings()->with('user')->orderBy('booked_at')->get();
		[$cancelled, $active] = $bookings->partition(fn (Booking $booking) => $booking->isCancelled());

		return Response::json([
			'event' => $event->number(),
			'course' => $event->course->getTranslation('title', 'de'),
			'dates' => $event->dateRange(),
			'state' => $event->state->value,
			'max_participants' => $event->max_participants,
			'cancelled' => $cancelled->count(),
			'bookings' => $active->map(fn (Booking $booking) => [
				'number' => $booking->number,
				'name' => $booking->user?->name,
				'company' => $booking->user?->company,
				'email' => $booking->user?->email,
				'booked_at' => $booking->booked_at?->toDateString(),
				'laptop_rental' => $booking->has_rental,
				'participated' => $booking->hasParticipated(),
			])->values()->all(),
		]);
	}

	public function schema(JsonSchema $schema): array
	{
		return [
			'event' => $schema->string()->required()->description('The Veranstaltung number (course number and start date, e.g. 05-161126) or its uuid.'),
		];
	}

	/** By uuid, or by number: the course's number and the start date, `dmy` ([[Event::number]]). */
	private function find(string $key): ?Event
	{
		$query = Event::query()->with(['course', 'dates']);

		if (preg_match('/^(\d+)-(\d{6})$/', $key, $match) === 1) {
			$date = Carbon::createFromFormat('dmy', $match[2]);

			return $query
				->whereHas('course', fn ($courses) => $courses->where('number', (int) $match[1]))
				->whereDate('date', $date->toDateString())
				->first();
		}

		return $query->where('uuid', $key)->first();
	}
}
