<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\EventState;
use App\Models\Event;
use App\Support\Documents\PdfRenderer;

/**
 * The list an expert takes into the room ([[08-accounts]], finding 5).
 *
 * **Bytes, and nothing else.** Where [[RenderInvoice]] and
 * [[RenderParticipationConfirmation]] file a `UserDocument`, this one does not:
 * the list belongs to a course rather than to a customer, it is out of date the
 * moment somebody cancels, and there is no version of it worth keeping.
 *
 * Legacy writes every one into `storage/app/public/files/`, records no row and
 * deletes nothing — **294 loose PDFs of names, phone numbers and email
 * addresses**, 27 MB, served through the `public/storage` symlink and
 * referenced by nothing, so nobody would notice if they were read
 * ([[Todo]], finding 3).
 *
 * The caller authorises with [[EventPolicy::viewParticipants]] before it gets
 * here.
 */
class RenderParticipantList
{
	public function __construct(private readonly PdfRenderer $renderer) {}

	public function execute(Event $event): string
	{
		$event->loadMissing(['course', 'experts']);

		/*
		 * The cancelled bookings where the course itself was called off, and
		 * the live ones otherwise — legacy's own rule, and the right one:
		 * cancelling a course cancels every seat on it, so the live list would
		 * be empty and the expert would lose the list of people they have to
		 * apologise to ([[ExpertPortalController::event]]).
		 */
		$bookings = $event->bookings()
			->when(
				$event->state === EventState::Cancelled,
				fn ($query) => $query->whereNotNull('cancelled_at'),
				fn ($query) => $query->active(),
			)
			->with('user')
			->get()
			->sortBy(fn ($booking) => $booking->user->last_name)
			->values();

		return $this->renderer->render('documents.participant-list', [
			'event' => $event,
			'bookings' => $bookings,
		]);
	}

	/** `viak-teilnehmerliste-27-161024.pdf` — the course, not a random string. */
	public function filename(Event $event): string
	{
		/*
		 * Legacy names these `viak-teilnehmerliste-{d-m-Y}-{12 random chars}.pdf`,
		 * which is what makes the 294 orphans on disk impossible to reconcile
		 * with anything. Named after the event it lists, so two downloads of the
		 * same list are the same file.
		 */
		return 'viak-teilnehmerliste-'.$event->number().'.pdf';
	}
}
