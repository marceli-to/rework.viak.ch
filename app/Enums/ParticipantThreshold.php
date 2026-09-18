<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How full a course is, as a state rather than a moment ([[06-bookings]]).
 *
 * Legacy notified on **equality and nothing else**:
 *
 *     if ($bookingsCount == $max)     // full
 *     if ($bookingsCount == $min)     // it will run
 *     if ($bookingsCount == $min - 1) // it might not
 *
 * Any step that skips the value loses the notification for good. Two bookings
 * landing in one cycle jump the count past `$max`, and nothing ever asks whether
 * the threshold has been *passed* — so nothing catches up. (The third test also
 * governs an unbraced statement, which is correct today and one edit from not
 * being.)
 *
 * Modelling it as a state fixes that by construction: the listener works out
 * which band the course is in now, compares it with the band recorded on the
 * event, and notifies only when it has changed. A skipped value still changes
 * the band; a repeated run does not.
 */
enum ParticipantThreshold: string
{
	/** Not enough people yet for the course to run. */
	case BelowMinimum = 'below_minimum';

	/** It will run. */
	case Viable = 'viable';

	/** No seats left. */
	case Full = 'full';
}
