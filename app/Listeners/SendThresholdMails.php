<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ParticipantThresholdCrossed;
use App\Mail\ParticipantsChange;
use Illuminate\Support\Facades\Mail;

/**
 * Tells the office a course date reached its minimum, filled up, or fell back
 * below the minimum ([[10-mail]]). The crossing is already decided, by band
 * rather than legacy's equality ([[NotifyParticipantThreshold]]); a jump from
 * below the minimum straight to full says both.
 */
class SendThresholdMails
{
	public function handle(ParticipantThresholdCrossed $crossed): void
	{
		if (! $office = config('mail.admin')) {
			return;
		}

		$event = $crossed->event->loadMissing(['course', 'dates', 'experts']);

		if ($crossed->becameViable()) {
			Mail::to($office)->send(new ParticipantsChange($event, 'min'));
		}

		if ($crossed->becameFull()) {
			Mail::to($office)->send(new ParticipantsChange($event, 'max'));
		}

		if ($crossed->fellBelowMinimum()) {
			Mail::to($office)->send(new ParticipantsChange($event, 'belowMin'));
		}
	}
}
