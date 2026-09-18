<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Events\BookingMade;
use App\Events\ParticipantThresholdCrossed;
use App\Models\Event;

/**
 * Tells VIAK when a course becomes viable, becomes full, or stops being either
 * ([[06-bookings]]).
 *
 * A listener because it is *pure notification* — the case the event/listener
 * rule in `00-foundation.md` was written for. Legacy's `ParticipantsChange` is
 * 59 lines that send three emails and change no state.
 *
 * What it fixes is legacy's equality test. Comparing `count == max` loses the
 * notification whenever a step skips the value, and nothing catches up. Here the
 * *band* is computed and compared with the band last recorded, so:
 *
 * - two bookings in one cycle that jump over the maximum still notify;
 * - a listener that runs twice on the same booking notifies once;
 * - a cancellation that drops a course back below its minimum notifies, and
 *   re-reaching the minimum notifies again.
 */
class NotifyParticipantThreshold
{
	public function handle(BookingMade|BookingCancelled $event): void
	{
		$this->evaluate($event->booking->event);
	}

	private function evaluate(Event $event): void
	{
		$was = $event->participant_threshold;
		$now = $event->currentThreshold();

		if ($was === $now) {
			return;
		}

		$event->forceFill(['participant_threshold' => $now])->save();

		// Never on first evaluation. A ported event has no recorded band, and
		// announcing thresholds crossed years ago is worse than saying nothing.
		if ($was === null) {
			return;
		}

		ParticipantThresholdCrossed::dispatch($event, $was, $now);
	}
}
