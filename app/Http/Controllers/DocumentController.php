<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hands a customer their own PDF ([[08-accounts]]).
 *
 * The authenticated replacement for legacy's arrangement, where the files sat
 * under the `public/storage` symlink and the only thing standing between an
 * invoice and anyone who wanted it was a uuid in the URL.
 */
class DocumentController extends Controller
{
	public function show(UserDocument $document): StreamedResponse
	{
		$this->authorize('view', $document);

		abort_unless(Storage::disk('documents')->exists($document->path()), 404);

		return Storage::disk('documents')->download(
			$document->path(),
			$document->filename,
			['Cache-Control' => 'private, no-store'],
		);
	}

	/** A Firmenkurs's invoice ([[RenderInvoice::forCompany]]); the route lets admins only in. */
	public function companyInvoice(Invoice $invoice): StreamedResponse
	{
		$path = $invoice->companyPdfPath();

		abort_unless($path !== null && Storage::disk('documents')->exists($path), 404);

		return Storage::disk('documents')->download($path, $invoice->filename, ['Cache-Control' => 'private, no-store']);
	}
}
