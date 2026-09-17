<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\EventState;
use App\Events\EventConfirmed;
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
 *
 * Confirming is where the money starts: it dispatches [[EventConfirmed]], which
 * raises an invoice for every seat ([[03-invoices]]). That is the domain rule,
 * not a convenience — a booking is a commitment, and only a confirmed event is
 * something there is anything to charge for.
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

		$wasConfirmed = $event->state === EventState::Confirmed;

		$event->update($attributes);
		$event->refresh();

		// Only on the transition *into* confirmed. Re-saving a confirmed event
		// must not re-bill it — the action that raises invoices is idempotent
		// anyway, but an event that fires on every save is one somebody will
		// eventually hang a second listener on. A seat sold on an already
		// confirmed event is billed at checkout instead, which is the same rule
		// from the other side ([[RaiseInvoiceForBooking]]).
		if ($state === EventState::Confirmed && ! $wasConfirmed) {
			EventConfirmed::dispatch($event);
		}

		return $event;
	}
}
