<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentType;
use App\Models\Invoice;
use App\Models\UserDocument;
use App\Support\Documents\PdfRenderer;
use App\Support\Documents\QrBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Makes the PDF a customer is sent, and records that it exists
 * ([[03-invoices]], [[08-accounts]]).
 *
 * ## The document is not a side effect of an email
 *
 * Legacy generates this inside a Mailable's constructor: `EventConfirmationStudent`
 * calls `Invoice::findOrCreateFromBooking()`, renders the PDF and inserts the
 * `user_documents` row **while composing a message**. So a document that is
 * part of the customer's financial record only exists if mail was sent, is made
 * again if the mail is retried, and cannot be produced at all without sending
 * one. `08-accounts.md` names that as the wrong shape; this is the right one —
 * the Action creates the document and the mail attaches what it made.
 *
 * ## It writes to the private disk
 *
 * Legacy writes to `storage/app/public/files/{user_uuid}/` and stores that
 * public path on the row. `public/storage` is symlinked, so **every invoice on
 * the live site is fetchable without authenticating**, protected only by a uuid
 * in the path (finding 3). The `documents` disk is not served by anything, and
 * [[DocumentController]] asks [[UserDocumentPolicy]] before it hands a file
 * over.
 *
 * ## Re-rendering replaces, and does not multiply
 *
 * Legacy has a `create()` that writes a row and an `update()` that does not —
 * two near-identical 40-line methods, where `update()` re-renders under a
 * filename built from the invoice's date, so moving an invoice's date leaves
 * the old file on disk and the row pointing at it. Here one method covers both:
 * the filename is derived from the invoice, the row is matched on the invoice
 * rather than blindly inserted, and a stale file is removed when the name
 * changes.
 */
class RenderInvoice
{
	public function __construct(
		private readonly PdfRenderer $renderer,
		private readonly QrBill $qrBill,
	) {}

	/**
	 * Render, store, and record. Returns the row a mail would attach.
	 */
	public function execute(Invoice $invoice): UserDocument
	{
		$invoice->loadMissing(['items', 'user']);

		if ($invoice->user === null) {
			// Every one of the 569 ported invoices has a user, and the column
			// is not nullable; this is the guard for a caller that built one by
			// hand, not for data.
			throw new RuntimeException("Invoice {$invoice->number} has no user to file a document against.");
		}

		$pdf = $this->renderer->render('documents.invoice', [
			'invoice' => $invoice,
			'qrBill' => $this->qrBill->html($invoice),
		]);

		$filename = $this->filename($invoice);
		$directory = 'documents/'.$invoice->user->uuid;

		return DB::transaction(function () use ($invoice, $pdf, $filename, $directory): UserDocument {
			$document = UserDocument::query()
				->where('user_id', $invoice->user->id)
				->where('documentable_type', Invoice::class)
				->where('documentable_id', $invoice->id)
				->where('type', DocumentType::Invoice)
				->first();

			// A re-render under a new name — an invoice whose date moved —
			// leaves the old file behind otherwise, and `Todo.md` already
			// carries 294 loose PDFs on the live site that nothing references.
			if ($document && $document->filename !== $filename) {
				Storage::disk('documents')->delete($directory.'/'.$document->filename);
			}

			Storage::disk('documents')->put($directory.'/'.$filename, $pdf);

			if ($document) {
				$document->update(['filename' => $filename, 'date' => $invoice->date]);
			} else {
				$document = UserDocument::create([
					'user_id' => $invoice->user->id,
					'type' => DocumentType::Invoice,
					'filename' => $filename,
					'date' => $invoice->date,
					'documentable_type' => Invoice::class,
					'documentable_id' => $invoice->id,
				]);
			}

			// The invoice carries the name of its own document, which is what
			// `Invoice::filename` was added for and what nothing filled until
			// now.
			$invoice->forceFill(['filename' => $filename])->save();

			return $document;
		});
	}

	/** The bytes, without storing anything — for a preview or a test. */
	public function preview(Invoice $invoice): string
	{
		return $this->renderer->render('documents.invoice', [
			'invoice' => $invoice->loadMissing(['items', 'user']),
			'qrBill' => $this->qrBill->html($invoice),
		]);
	}

	/**
	 * `viak-rechnung-02-10-2024-000300.pdf` — legacy's pattern exactly, so the
	 * 568 ported files and everything written from here on read alike in a
	 * customer's downloads folder.
	 *
	 * Legacy builds it from `$invoice->date_short` with the dots replaced,
	 * which is the same thing said twice as far apart as possible.
	 */
	private function filename(Invoice $invoice): string
	{
		return 'viak-rechnung-'.$invoice->date->format('d-m-Y').'-'.$invoice->number.'.pdf';
	}
}
