<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventState;
use App\Mail\EventCancelOrConfirmReminder;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Reminds the office to confirm or cancel a date that is ten days away or
 * less and still only planned ([[10-mail]]) — legacy's `Tasks/ObserveEventState`.
 *
 * **Crossed, and not yet reminded**, where legacy matched the tenth day
 * exactly: one run missed on that day and the date was never reminded
 * (`Todo.md`). Here a date is reminded once, whenever the first run after it
 * enters the window happens, and `reminded_at` says when.
 */
class RemindUndecidedEvents extends Command
{
	protected $signature = 'events:remind';

	protected $description = 'Remind the office of planned course dates ten days away or less';

	public const DAYS = 10;

	public function handle(): int
	{
		$office = config('mail.admin');

		if (! $office) {
			$this->warn('No office address (MAIL_ADMIN_ADDRESS); nothing sent.');

			return self::SUCCESS;
		}

		$events = Event::query()
			->where('state', EventState::Planned)
			->whereNull('reminded_at')
			->whereDate('date', '>=', today())
			->whereDate('date', '<=', today()->addDays(self::DAYS))
			->with(['course', 'dates', 'experts'])
			->get();

		foreach ($events as $event) {
			Mail::to($office)->send(new EventCancelOrConfirmReminder($event));
			$event->forceFill(['reminded_at' => now()])->save();
		}

		$this->info("Reminded {$events->count()} date(s).");

		return self::SUCCESS;
	}
}
