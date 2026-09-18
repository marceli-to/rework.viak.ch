<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Booking;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * What a student owes for cancelling late ([[06-bookings]]).
 *
 * ## The rule
 *
 * Counting days from today to the event: inside 11 days the whole fee is due,
 * inside 20 days half of it, and earlier than that nothing. A cancellation
 * *after* the course has run owes the whole fee, which the signed comparison
 * below gives for free.
 *
 * ## Why this is not a straight port
 *
 * Legacy's `PenaltyHelper` computes
 *
 *     $days = Carbon::parse($eventDate)->diffInDays(Carbon::now());
 *
 * and locks **Carbon 2.73**, where `diffInDays()` returns an **absolute**
 * value. So `$days` is days-until-the-event and the rule reads correctly.
 *
 * The rework runs Laravel 13 and **Carbon 3.13, where `diffInDays()` is
 * signed**. Verified in both trees against the same two dates:
 *
 *     Carbon 2 (legacy):  $event->diffInDays($now)  =  33
 *     Carbon 3 (rework):  $event->diffInDays($now)  = -33
 *
 * Ported verbatim, `-33 < 11` is true and **every cancellation of a future
 * event becomes a 100 % penalty** — silently, on live money. So this computes
 * the number of days it actually means, in the direction it means, and both
 * boundaries are pinned by tests.
 *
 * ## The second test legacy applied
 *
 * `PenaltyHelper::applies()` also required the fee to exceed the discount. One
 * of the nine late cancellations in the data was fully discounted and so exempt
 * — a student who paid nothing owes nothing. That is kept.
 */
final class CancellationPenalty
{
	/** Days from a given day to the event: positive before, negative after. */
	public function daysUntil(Booking $booking, ?DateTimeInterface $on = null): int
	{
		$from = ($on ? Carbon::parse($on) : Carbon::now())->startOfDay();

		// The second argument is what matters: `false` means "do not take the
		// absolute value". Carbon 3 is signed by default, Carbon 2 was not, and
		// this is the line the whole class exists to get right.
		return (int) $from->diffInDays($booking->event->date->copy()->startOfDay(), false);
	}

	/**
	 * The share of the net fee that is due, as a decimal string: `1.00`, `0.50`
	 * or `0.00`.
	 */
	public function rate(Booking $booking, ?DateTimeInterface $on = null): string
	{
		$days = $this->daysUntil($booking, $on);

		if ($days < (int) config('invoice.penalty_full_days')) {
			return '1.00';
		}

		if ($days < (int) config('invoice.penalty_half_days')) {
			return '0.50';
		}

		return '0.00';
	}

	/**
	 * What the student owes, in francs, or `0.00` where nothing is due.
	 *
	 * Charged on the **net** fee — what was actually agreed after any discount —
	 * because charging the list price to someone who was given a code would
	 * invoice them more than the course itself would have cost.
	 */
	public function amount(Booking $booking, ?DateTimeInterface $on = null): string
	{
		$net = $booking->netFee();

		if (bccomp($net, '0.00', 2) <= 0) {
			return '0.00';
		}

		return bcmul($net, $this->rate($booking, $on), 2);
	}

	/** Is there a penalty invoice to raise at all? */
	public function applies(Booking $booking, ?DateTimeInterface $on = null): bool
	{
		return bccomp($this->amount($booking, $on), '0.00', 2) > 0;
	}
}
