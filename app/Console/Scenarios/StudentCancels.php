<?php

declare(strict_types=1);

namespace App\Console\Scenarios;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;

/** A student drops out twice: once early and free, once late and charged. */
class StudentCancels extends Scenario
{
	private Event $event;

	private User $anna;

	private Booking $booking;

	public static function description(): string
	{
		return 'A student cancels early without a penalty, books again, then cancels five days out and gets the penalty invoice';
	}

	public function steps(): array
	{
		return [
			'Anna and Beat book a date 40 days out' => function () {
				$this->event = $this->stage->event(daysOut: 40);
				$this->anna = $this->stage->student('Anna');
				$this->booking = $this->stage->book($this->anna, $this->event);
				$this->stage->book($this->stage->student('Beat'), $this->event);

				return null;
			},
			'Anna cancels, 40 days out' => function () {
				$this->stage->cancel($this->booking);

				return 'Too early for a penalty; the date drops below its minimum.';
			},
			'Anna books again' => function () {
				$this->booking = $this->stage->book($this->anna, $this->event);

				return null;
			},
			'Five days before the course, Anna cancels again' => function () {
				$this->stage->travelTo($this->event->date->copy()->subDays(5)->setTime(10, 0));
				$invoice = $this->stage->cancel($this->booking);

				return 'Inside the full-fee window: penalty invoice '.($invoice?->number ?? '(none)').'.';
			},
		];
	}
}
