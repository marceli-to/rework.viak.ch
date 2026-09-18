<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Binds uploads to the thing they belong to ([[08-accounts]]).
 *
 * Moves each file out of `temp/` and into `uploads/`, then writes the row. New
 * items go on the end of whatever order already exists.
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

			Storage::disk('public')->move($temp, 'uploads/'.$media->file);

			$media->sort_order = ++$sort;
			$media->mediable()->associate($parent);
			$media->save();

			$attached[] = $media;
		}

		return $attached;
	}
}
