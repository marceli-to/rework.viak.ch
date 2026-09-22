<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentType;
use App\Models\Booking;
use App\Models\UserDocument;
use App\Support\Documents\PdfRenderer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The certificate a student gets when their course is closed ([[08-accounts]]).
 *
 * The sibling of [[RenderInvoice]] and the same three corrections: it is an
 * Action rather than a Mailable's constructor, it writes to the private disk
 * rather than under the `public/storage` symlink, and re-running it replaces
 * the customer's document instead of filing a second one.
 *
 * ## The date, which legacy has two of
 *
 * `EventParticipationConfirmation` dates the document `Zürich, {closed_at}`,
 * files the row under `date => closed_at`, and then builds the **filename** out
 * of `date('d-m-Y', time())` — *today*. So a confirmation reissued a year later
 * is filed under a date that appears nowhere on the document, and two
 * confirmations for the same booking cannot be told apart by name. One date
 * here, the event's, used everywhere.
 *
 * **And `closed_at` can be null.** Legacy writes `date => $booking->event->closed_at`
 * without checking, which is how a `PARTICIPATION_CONFIRMATION` row acquires a
 * null date; the event's own date is the fallback, and it is never null.
 */
class RenderParticipationConfirmation
{
	public function __construct(private readonly PdfRenderer $renderer) {}

	public function execute(Booking $booking): UserDocument
	{
		$booking->loadMissing(['event.course', 'event.dates', 'event.experts', 'user']);

		$pdf = $this->renderer->render('documents.participation-confirmation', [
			'booking' => $booking,
		]);

		$date = $booking->event->closed_at ?? $booking->event->date;
		$filename = 'viak-teilnahmebestaetigung-'.$date->format('d-m-Y').'-'.$booking->number.'.pdf';
		$directory = 'documents/'.$booking->user->uuid;

		return DB::transaction(function () use ($booking, $pdf, $filename, $directory, $date): UserDocument {
			$document = UserDocument::query()
				->where('user_id', $booking->user_id)
				->where('documentable_type', Booking::class)
				->where('documentable_id', $booking->id)
				->where('type', DocumentType::ParticipationConfirmation)
				->first();

			if ($document && $document->filename !== $filename) {
				Storage::disk('documents')->delete($directory.'/'.$document->filename);
			}

			Storage::disk('documents')->put($directory.'/'.$filename, $pdf);

			if ($document) {
				$document->update(['filename' => $filename, 'date' => $date]);

				return $document;
			}

			return UserDocument::create([
				'user_id' => $booking->user_id,
				'type' => DocumentType::ParticipationConfirmation,
				'filename' => $filename,
				'date' => $date,
				'documentable_type' => Booking::class,
				'documentable_id' => $booking->id,
			]);
		});
	}
}
