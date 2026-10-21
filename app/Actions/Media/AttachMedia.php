<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Binds uploads to the thing they belong to ([[08-accounts]]).
 *
 * Moves each file out of `temp/` to where its owner keeps it ([[Media::disk]]):
 * `uploads/` for an image, the private disk for a Veranstaltung's or a
 * message's file. New items go on the end of whatever order already exists.
 */
class AttachMedia
{
	/**
	 * @param  array<int, Media>  $items
	 * @return array<int, Media>
	 */
	public function execute(array $items, Model $parent): array
	{
		$sort = (int) ($parent->media()->max('sort_order') ?? -1);
		$attached = [];

		foreach ($items as $media) {
			$temp = 'temp/'.$media->file;

			// Silently skipped rather than thrown: an upload can be abandoned,
			// and a half-finished form resubmitted. Nothing is lost by ignoring
			// a file that is not there.
			if (! Storage::disk('public')->exists($temp)) {
				continue;
			}

			$media->sort_order = ++$sort;
			$media->mediable()->associate($parent);

			if ($media->isAttachment()) {
				$media->disk()->writeStream($media->path(), Storage::disk('public')->readStream($temp));
				Storage::disk('public')->delete($temp);
			} else {
				Storage::disk('public')->move($temp, $media->path());
			}

			$media->save();

			$attached[] = $media;
		}

		return $attached;
	}
}
