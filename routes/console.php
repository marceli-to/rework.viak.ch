<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
	$this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * **The queue worker runs from cron** (Marcel, 2026-09-29; [[00-foundation]],
 * *Queue and schedule*). Production has one crontab line,
 *
 *     * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
 *
 * and each minute that starts a worker which empties the queue and stops, at
 * the latest after 55 seconds so the next minute's never overlaps it. Mail,
 * PDFs and accounting posts wait at most a minute, with retries and
 * `failed_jobs` — where legacy's own `Tasks/Job` sent two mails a minute and
 * dropped any that failed.
 *
 * In the background, so a long queue does not hold up the rest of the
 * schedule. Locally, `composer dev` runs a listener instead.
 */
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3 --backoff=60')
	->everyMinute()
	->withoutOverlapping()
	->runInBackground();

/*
 * Planned dates ten days away or less, not yet reminded ([[RemindUndecidedEvents]]).
 * Hourly rather than legacy's once-a-minute exact-day match: it asks "crossed,
 * and not yet reminded", so a missed run only delays a reminder.
 */
Schedule::command('events:remind')->hourly()->withoutOverlapping();

// Failed jobs are kept a month to look into, then go.
Schedule::command('queue:prune-failed --hours=720')->daily();

/*
 * Nightly, and only in production: outside it the accounting system is a fake
 * with nothing to report ([[AccountingSystem]]).
 */
Schedule::command('invoices:sync')->dailyAt('01:00')->environments('production')->withoutOverlapping();
