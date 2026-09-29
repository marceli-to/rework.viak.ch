<?php

declare(strict_types=1);

namespace App\Console\Scenarios;

use App\Actions\Events\SetEventState;
use App\Enums\EventState;
use App\Models\Booking;
use App\Models\Event;

/** Register, book to the minimum, confirm, close: the flow that runs a course. */
class Confirm extends Scenario
{
	private Event $event;

	private Booking $anna;

	private Booking $beat;

	public static function description(): string
	{
		return 'Two sign-ups book up to the minimum, the date is confirmed with invoices, then closed with a certificate for who attended';
	}

	public function steps(): array
	{
		return [
			'Anna signs up on the site and books' => function () {
				$this->event = $this->stage->event();
				$this->anna = $this->stage->book($this->stage->register('Anna'), $this->event);

				return null;
			},
			'Beat signs up and books: the date reaches its minimum of two' => function () {
				$this->beat = $this->stage->book($this->stage->register('Beat'), $this->event);

				return null;
			},
			'The office confirms the date' => function () {
				app(SetEventState::class)->execute($this->event->refresh(), EventState::Confirmed);

				return 'Each seat is invoiced, and the invoice rides on the confirmation.';
			},
			'The course has been held; only Anna is ticked as attended' => function () {
				$this->stage->travelTo($this->event->date->copy()->addDay()->setTime(10, 0));
				$this->anna->forceFill(['participated_at' => now()])->save();

				return 'The clock is now the day after the course. Nothing is sent by a tick.';
			},
			'The office closes the date' => function () {
				app(SetEventState::class)->execute($this->event->refresh(), EventState::Closed);

				return 'Beat was not ticked, so Beat gets nothing (Marcel, 2026-09-29).';
			},
		];
	}
}
