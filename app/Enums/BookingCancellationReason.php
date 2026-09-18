<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who cancelled a booking, and therefore whether there is a penalty
 * ([[06-bookings]]).
 *
 * Legacy had no such column. The distinction existed only as an accident of
 * structure: `Booking::cancel()` ran `PenaltyHelper` and `EventCancelledHandler`
 * flagged the rows directly without going near it. Nothing anywhere stated the
 * rule, and it was one refactor away from disappearing.
 *
 * The cost of losing it is measurable. Of the 183 cancelled bookings, 143 are on
 * courses VIAK itself called off — and **115 of those fall inside the 0–10 day
 * window**, because VIAK decides late whether a course runs. Merge the two paths
 * without this column and 115 students get invoiced the full fee for a course
 * that never happened.
 *
 * `Administrator` is the case legacy could not express at all. An admin
 * cancelling on a student's behalf went through the student's own route
 * (`role:admin,student` on `PUT /api/booking/cancel`), so the two were
 * indistinguishable in the data and the penalty fired either way
 * ([[08-accounts]]).
 */
enum BookingCancellationReason: string
{
	/** The student cancelled. The penalty window applies. */
	case Student = 'student';

	/**
	 * An admin cancelled on the student's behalf — a phone call, usually.
	 *
	 * **Open question 14: does this charge the penalty?** Until Marcel says
	 * otherwise it behaves like `Student`, which is what legacy did, because
	 * the two shared a route. The difference is that the rework now *records*
	 * which one happened, so the answer can change without rewriting history.
	 */
	case Administrator = 'administrator';

	/** VIAK called the course off. Never a penalty — the student did nothing. */
	case EventCancelled = 'event_cancelled';

	/**
	 * Does the cancellation window apply at all?
	 *
	 * The one branch this enum exists for. Keep it here rather than in the
	 * Action so that adding a case forces a decision about it.
	 */
	public function chargesPenalty(): bool
	{
		return match ($this) {
			self::Student, self::Administrator => true,
			self::EventCancelled => false,
		};
	}

	/** Whether the student themselves initiated it, for the notification copy. */
	public function isStudentInitiated(): bool
	{
		return $this === self::Student;
	}
}
