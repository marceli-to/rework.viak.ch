<?php

declare(strict_types=1);

namespace App\Console\Scenarios;

use App\Models\Event;
use Illuminate\Support\Facades\Artisan;

/**
 * The office's reminder to decide a date ten days out, sent once. Runs the
 * scheduled command itself, narrowed to this date so the ported ones are left
 * alone ([[RemindUndecidedEvents]]).
 */
class Reminder extends Scenario
{
	private Event $event;

	public static function description(): string
	{
		return 'A planned date comes within ten days: the office is reminded once, and not again';
	}

	public function steps(): array
	{
		return [
			'The hourly reminder runs, eleven days before a planned date' => function () {
				$this->event = $this->stage->event(daysOut: 11);

				return $this->remind();
			},
			'A day later, it runs again' => function () {
				$this->stage->travelTo(now()->addDay());

				return $this->remind();
			},
			'And again, an hour later' => function () {
				$this->stage->travelTo(now()->addHour());

				return $this->remind();
			},
		];
	}

	private function remind(): string
	{
		Artisan::call('events:remind', ['--event' => [$this->event->uuid]]);

		return trim(Artisan::output());
	}
}
