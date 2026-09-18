<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Removes an upload and its file ([[08-accounts]]).
 *
 * Hard delete, matching what legacy actually did: of the 492 image rows, the 159
 * soft-deleted ones have **no file on disk** — deleting an image removed it.
 * Keeping a row whose file is gone only produces broken images later.
 */
class DeleteMedia
{
	public function execute(Media $media): void
	{
		Storage::disk('public')->delete('uploads/'.$media->file);

		$media->delete();
	}
}
