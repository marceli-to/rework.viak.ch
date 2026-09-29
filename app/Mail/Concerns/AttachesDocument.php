<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use App\Models\UserDocument;
use Illuminate\Mail\Attachment;

/** A PDF from the documents disk, under its own name ([[UserDocument::path]]). */
trait AttachesDocument
{
	/** @return array<int, Attachment> */
	protected function attachDocument(?UserDocument $document): array
	{
		return $document
			? [Attachment::fromStorageDisk('documents', $document->path())->as($document->filename)->withMime('application/pdf')]
			: [];
	}
}
