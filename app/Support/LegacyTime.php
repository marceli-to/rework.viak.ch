<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * A moment off legacy's database, in this app's time zone.
 *
 * Legacy ran in UTC and so stored UTC: its messages start at 04:00 read that
 * way, 06:00 in Zurich, where read as Zurich they would start before dawn.
 * This app runs in `Europe/Zurich` (Marcel, 2026-09-30, open question #40), and
 * both connections read and write `TIMESTAMP`s at `+00:00`
 * (`config/database.php`), so what the port reads is legacy's own string and
 * what it writes is stored as given. The shift between the two is this.
 *
 * **Moments only.** A `DATE` — an event's day, an invoice's date, `due_at` —
 * is a calendar day and goes across as it is.
 */
final class LegacyTime
{
	public static function local(?string $utc): ?string
	{
		return $utc === null
			? null
			: Carbon::parse($utc, 'UTC')->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
	}
}
