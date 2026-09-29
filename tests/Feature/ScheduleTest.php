<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

/**
 * The queue worker runs from cron ([[00-foundation]], *Queue and schedule*):
 * with only `schedule:run` in the crontab, nothing queued is ever sent unless
 * the schedule starts a worker every minute.
 */
it('starts a queue worker every minute that empties the queue and stops in time', function () {
	$events = collect(app(Schedule::class)->events());
	$worker = $events->first(fn (Event $event) => str_contains($event->command, 'queue:work'));

	expect($worker)->not->toBeNull()
		->and($worker->expression)->toBe('* * * * *')
		->and($worker->command)->toContain('--stop-when-empty')->toContain('--max-time=55')
		->and($worker->withoutOverlapping)->toBeTrue()
		->and((int) config('queue.connections.database.retry_after'))->toBeGreaterThan(55);
});
