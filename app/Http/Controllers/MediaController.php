<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hands over an attachment — a course's materials, or a file on a message
 * ([[08-accounts]]).
 *
 * The sibling of [[DocumentController]], and the same argument: legacy's
 * `FileController` served any row to any authenticated user, and the files sit
 * under the `public/storage` symlink besides. This one asks [[MediaPolicy]].
 *
 * **The file is still on the public disk, and that is not finished.** Moving the
 * 13 course materials off it is a port change rather than a view change, so this
 * closes the link the portal hands out and leaves the direct path reachable;
 * `Todo.md` carries the move. Worth saying rather than implying the hole is
 * shut — it is the same disk the 1,005 documents were taken off, and they were
 * moved because the storage decision came first ([[01-schema]]).
 */
class MediaController extends Controller
{
	public function download(Media $media): StreamedResponse
	{
		$this->authorize('download', $media);

		$path = 'uploads/'.$media->file;

		abort_unless(Storage::disk('public')->exists($path), 404);

		return Storage::disk('public')->download(
			$path,
			$media->original_name ?? $media->file,
			['Cache-Control' => 'private, no-store'],
		);
	}
}
