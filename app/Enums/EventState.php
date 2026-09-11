<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle of a course date ([[02-courses-events]]).
 *
 * Replaces the legacy dual-write, where state lived in BOTH nullable timestamp
 * columns (`confirmed_at`, `cancelled_at`, `closed_at`) AND rows in the
 * spatie/laravel-model-flags `flags` table. The two disagreed on event 99 in
 * production data: `confirmed_at` set, no `isConfirmed` flag — and since the
 * app read flags, it treated a confirmed event as unconfirmed.
 *
 * Here `events.state` is the single source of truth. The timestamps stay, but
 * only as an audit trail of when the transition happened.
 *
 * - Planned:   created, registration open, not yet going ahead.
 * - Confirmed: enough participants, it runs. Students are told.
 * - Cancelled: called off. Bookings are cancelled, penalties may apply.
 * - Closed:    registration shut (full, or past its registration_until).
 */
enum EventState: string
{
	case Planned = 'planned';
	case Confirmed = 'confirmed';
	case Cancelled = 'cancelled';
	case Closed = 'closed';

	/** Can a student still book onto an event in this state? */
	public function acceptsBookings(): bool
	{
		return $this === self::Planned || $this === self::Confirmed;
	}

	/** The timestamp column recording entry into this state, if any. */
	public function timestampColumn(): ?string
	{
		return match ($this) {
			self::Confirmed => 'confirmed_at',
			self::Cancelled => 'cancelled_at',
			self::Closed => 'closed_at',
			self::Planned => null,
		};
	}
}
