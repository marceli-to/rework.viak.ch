<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use App\Models\Booking;
use App\Models\Event;
use Illuminate\Console\Command;

/**
 * Veranstaltungen with more laptops rented than the room has ([[06-bookings]],
 * *Laptops are counted*; `.rework/Deploy.md`).
 *
 * The rework refuses a new laptop past `rentals_available`, but the port
 * copies bookings as legacy holds them. A count lowered after a laptop was
 * booked leaves a rental over it, as on event `eac2d8b5…` in the 2026-09-11
 * copy: one laptop booked in a room legacy says has none. So every date that
 * has not happened yet and is over its count is listed, with the bookings
 * holding a laptop, for VIAK to settle before the rental is invoiced: raise
 * the count, or drop the laptop.
 *
 * Fails while there is one. Past dates are listed with `--all` and never
 * fail; nothing is left to settle on them. Reads only.
 */
class CheckRentals extends Command
{
	protected $signature = 'port:check-rentals {--all : Past dates too}';

	protected $description = 'List Veranstaltungen with more laptops rented than the room has';

	public function handle(): int
	{
		$events = Event::query()
			->with('course')
			->withCount(['bookings as rentals_taken_count' => fn ($query) => $query->active()->where('has_rental', true)])
			->unless($this->option('all'), fn ($query) => $query->whereDate('date', '>=', today()))
			->orderBy('date')
			->get()
			->filter(fn (Event $event) => $event->rentals_taken_count > $event->rentals_available);

		if ($events->isEmpty()) {
			$this->info('No Veranstaltung has more laptops rented than its room has.');

			return self::SUCCESS;
		}

		$this->table(
			['date', 'course', 'event', 'laptops', 'rented', 'bookings holding one'],
			$events->map(fn (Event $event) => [
				$event->date->format('d.m.Y'),
				$event->course?->getTranslation('title', 'de'),
				$event->uuid,
				$event->rentals_available,
				$event->rentals_taken_count,
				$event->bookings()->active()->where('has_rental', true)->with('user')->get()
					->map(fn (Booking $booking) => $booking->number.' '.$booking->user?->name)->implode(', '),
			])->all(),
		);

		$upcoming = $events->filter(fn (Event $event) => ! $event->date->isPast() || $event->date->isToday());

		if ($upcoming->isEmpty()) {
			return self::SUCCESS;
		}

		$this->error($upcoming->count().' upcoming Veranstaltung(en) to settle with VIAK: raise the room\'s count, or drop a laptop.');

		return self::FAILURE;
	}
}
