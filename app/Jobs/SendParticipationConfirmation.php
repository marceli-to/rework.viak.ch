<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Documents\RenderParticipationConfirmation;
use App\Mail\EventClosedStudent;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * The participation confirmation for one attended seat ([[10-mail]]): the PDF
 * made by [[RenderParticipationConfirmation]] (the same one if it exists), then
 * mailed. On the queue, one per attendee, as dompdf is slow.
 */
class SendParticipationConfirmation implements ShouldQueue
{
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;

	public function __construct(public readonly Booking $booking)
	{
		$this->afterCommit();
	}

	public function handle(RenderParticipationConfirmation $render): void
	{
		Mail::to($this->booking->user)->send(new EventClosedStudent($this->booking, $render->execute($this->booking)));
	}
}
