<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\EventState;
use App\Models\Event;
use RuntimeException;

/**
 * Moves an event through its lifecycle ([[EventState]]).
 *
 * This is the *only* place `events.state` is written. Legacy set a spatie flag
 * in one place and a timestamp column in another, and the two drifted — see the
 * note on [[EventState]]. Writing both here, together, keeps the audit trail
 * honest without letting it become a second source of truth.
 *
 * Cancelling is terminal: a cancelled event has told its students it is off and
 * may have triggered penalty invoices, so it cannot quietly come back.
 */
class SetEventState
{
	public function execute(Event $event, EventState $state): Event
	{
		if ($event->state === EventState::Cancelled && $state !== EventState::Cancelled) {
			throw new RuntimeException(
				"Event {$event->uuid} is cancelled; reinstating it would contradict what students were told."
			);
		}

		$attributes = ['state' => $state];

		if ($column = $state->timestampColumn()) {
			$attributes[$column] = now();
		}

		$event->update($attributes);

		return $event->refresh();
	}
}
