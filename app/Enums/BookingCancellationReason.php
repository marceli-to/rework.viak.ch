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
	 * An admin cancelled on the student's behalf — a phone call, usually —
	 * and the penalty window applies, as for `Student`.
	 *
	 * **The admin decides, per cancellation** (#14, 2026-09-24): the student
	 * page asks whenever there is a cost. This is the answer *charge it*, and
	 * also any admin cancellation that costs nothing.
	 */
	case Administrator = 'administrator';

	/**
	 * An admin cancelled on the student's behalf and **let the cost go** (#14).
	 * Recorded as a reason of its own, only when there was a cost to let go,
	 * so the waiver stays readable in the data. At 20 characters it fills the
	 * column exactly.
	 */
	case AdministratorWaived = 'administrator_waived';

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
			self::AdministratorWaived, self::EventCancelled => false,
		};
	}

	/** Who cancelled, as the dashboard's student page says it. */
	public function label(): string
	{
		return match ($this) {
			self::Student => 'vom Studenten',
			self::Administrator => 'von VIAK',
			self::AdministratorWaived => 'von VIAK, ohne Kosten',
			self::EventCancelled => 'weil der Kurs abgesagt wurde',
		};
	}

	/** Whether the student themselves initiated it, for the notification copy. */
	public function isStudentInitiated(): bool
	{
		return $this === self::Student;
	}
}
