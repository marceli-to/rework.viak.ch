<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Event;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * The dashboard's *Kurse* screen as a list: what runs next, and how full it is
 * ([[17-mcp]]). Cancelled Veranstaltungen are left out; unpublished ones are in,
 * marked, because the office plans those too.
 */
#[Description('Upcoming Veranstaltungen (course runs): number, course, dates, location, state, seats taken against minimum and maximum, registration deadline, fee. Cancelled ones are left out.')]
#[IsReadOnly]
class UpcomingEvents extends Tool
{
	public function handle(Request $request): Response
	{
		$input = $request->validate([
			'days' => ['nullable', 'integer', 'min:1', 'max:730'],
			'course' => ['nullable', 'string', 'max:100'],
		]);

		$until = today()->addDays((int) ($input['days'] ?? 90));
		$course = trim((string) ($input['course'] ?? ''));

		$events = Event::query()
			->active()
			->upcoming()
			->whereDate('date', '<=', $until)
			// `LOWER`: a value read out of JSON is compared in binary, so *rhino* would miss *Rhino*.
			->when($course !== '', fn (Builder $query) => $query->whereHas('course', fn (Builder $courses) => $courses
				->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.de'))) LIKE ?", ['%'.addcslashes(mb_strtolower($course), '%_\\').'%'])))
			->with(['course', 'location', 'dates'])
			->withCount(['bookings as seats_taken' => fn (Builder $bookings) => $bookings->whereNull('cancelled_at')])
			->get();

		return Response::json([
			'until' => $until->toDateString(),
			'count' => $events->count(),
			'events' => $events->map(fn (Event $event) => [
				'number' => $event->number(),
				'uuid' => $event->uuid,
				'course' => $event->course->getTranslation('title', 'de'),
				'dates' => $event->dateRange(),
				'location' => $event->online ? 'Online' : $event->location?->getTranslation('description', 'de'),
				'state' => $event->state->value,
				'published' => (bool) $event->publish,
				'seats_taken' => $event->seats_taken,
				'min_participants' => $event->min_participants,
				'max_participants' => $event->max_participants,
				'registration_until' => $event->registration_until?->toDateString(),
				'fee' => $event->fee(),
			])->values()->all(),
		]);
	}

	public function schema(JsonSchema $schema): array
	{
		return [
			'days' => $schema->integer()->min(1)->max(730)->description('How far ahead to look, in days. Default 90.'),
			'course' => $schema->string()->description('Only courses whose title contains this text, e.g. "Rhino".'),
		];
	}
}
