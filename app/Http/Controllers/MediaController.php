<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hands over an attachment — a course's materials, or a file on a message
 * ([[08-accounts]]).
 *
 * The sibling of [[DocumentController]], and the same argument: legacy's
 * `FileController` served any row to any authenticated user, and the files sit
 * under the `public/storage` symlink besides. This one asks [[MediaPolicy]].
 *
 * **The files are on the private `attachments` disk** since 2026-10-21, so
 * this is the only way to them; until then they sat in the public `uploads/`
 * and the direct path skipped the policy ([[Media::disk]]).
 */
class MediaController extends Controller
{
	public function download(Media $media): StreamedResponse
	{
		$this->authorize('download', $media);

		abort_unless($media->disk()->exists($media->path()), 404);

		return $media->disk()->download(
			$media->path(),
			$media->original_name ?? $media->file,
			['Cache-Control' => 'private, no-store'],
		);
	}
}
