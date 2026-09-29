<?php

declare(strict_types=1);

namespace App\Console\Scenarios;

use App\Actions\Events\SetEventState;
use App\Actions\Messages\PostMessage;
use App\Enums\EventState;
use App\Models\Event;
use App\Models\User;

/** Someone books a course that is already confirmed and already talking. */
class LateBooker extends Scenario
{
	private Event $event;

	private User $expert;

	public static function description(): string
	{
		return 'A confirmed date with two expert notes gets a new booking: confirmation, invoice and every earlier note';
	}

	public function steps(): array
	{
		return [
			'Anna and Beat book, and the office confirms the date' => function () {
				$this->expert = $this->stage->expert('Kevin');
				$this->event = $this->stage->event(expert: $this->expert);
				$this->stage->book($this->stage->student('Anna'), $this->event);
				$this->stage->book($this->stage->student('Beat'), $this->event);
				app(SetEventState::class)->execute($this->event->refresh(), EventState::Confirmed);

				return null;
			},
			'Kevin posts two notes to the course, with a copy to himself' => function () {
				$post = app(PostMessage::class);
				$post->execute($this->event, $this->expert, 'Raum und Anreise', '<p>Wir treffen uns im Kursraum im 2. Stock.</p>', copyToAuthor: true);
				$post->execute($this->event, $this->expert, 'Vorbereitung', '<p>Bitte die Testversion vorab installieren.</p>');

				return null;
			},
			'Carla books the confirmed date' => function () {
				$this->stage->book($this->stage->student('Carla'), $this->event);

				return 'Booked on a confirmed date: invoiced at checkout, and sent each earlier note, one mail apiece.';
			},
		];
	}
}
