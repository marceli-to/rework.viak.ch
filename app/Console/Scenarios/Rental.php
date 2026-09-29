<?php

declare(strict_types=1);

namespace App\Console\Scenarios;

use App\Actions\Bookings\SetRental;
use App\Models\Booking;
use App\Models\Event;

/** The rented laptop: with the booking, added later, dropped again. */
class Rental extends Scenario
{
	private Event $event;

	private Booking $beat;

	public static function description(): string
	{
		return 'A laptop booked with the seat, one added later and dropped again';
	}

	public function steps(): array
	{
		return [
			'Anna books with a laptop' => function () {
				$this->event = $this->stage->event();
				$this->stage->book($this->stage->student('Anna'), $this->event, rental: true);

				return null;
			},
			'Beat books without one' => function () {
				$this->beat = $this->stage->book($this->stage->student('Beat'), $this->event);

				return null;
			},
			'Beat adds a laptop' => function () {
				app(SetRental::class)->execute($this->beat->refresh(), true);

				return null;
			},
			'Beat drops it again' => function () {
				app(SetRental::class)->execute($this->beat->refresh(), false);

				return null;
			},
		];
	}
}
