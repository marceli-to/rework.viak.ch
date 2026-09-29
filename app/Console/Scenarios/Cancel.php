<?php

declare(strict_types=1);

namespace App\Console\Scenarios;

use App\Actions\Events\SetEventState;
use App\Enums\EventState;
use App\Models\Event;

/** VIAK calls a date off after two people have booked it. */
class Cancel extends Scenario
{
	private Event $event;

	public static function description(): string
	{
		return 'Two sign-ups book, then VIAK cancels the date: students and the expert are told, and nobody is charged';
	}

	public function steps(): array
	{
		return [
			'Anna and Beat sign up and book' => function () {
				$this->event = $this->stage->event();
				$this->stage->book($this->stage->register('Anna'), $this->event);
				$this->stage->book($this->stage->register('Beat'), $this->event);

				return null;
			},
			'The office cancels the date' => function () {
				app(SetEventState::class)->execute($this->event->refresh(), EventState::Cancelled);

				return 'Kursabsage only: a seat VIAK gave up sends none of the cancellation mails.';
			},
		];
	}
}
