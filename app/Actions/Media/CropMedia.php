<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Media;

/**
 * Records where the image should be cut ([[08-accounts]]).
 *
 * Nothing is rendered here. The crop is four numbers in source pixels, stored on
 * the row and handed to Glide at request time — so re-cropping is instant and
 * costs no disk, and the original is never touched.
 *
 * Null clears it. Legacy expressed that as `0,0,0,0` on 85 of its 492 rows and
 * then had to special-case the string to avoid producing a 1×1 image.
 */
class CropMedia
{
	/** @param  array{x: ?int, y: ?int, w: ?int, h: ?int}  $crop */
	public function execute(Media $media, array $crop): Media
	{
		$cleared = ($crop['w'] ?? null) === null || ($crop['h'] ?? null) === null
			|| (int) $crop['w'] <= 0 || (int) $crop['h'] <= 0;

		$media->update(['crop' => $cleared ? null : [
			'x' => (int) $crop['x'],
			'y' => (int) $crop['y'],
			'w' => (int) $crop['w'],
			'h' => (int) $crop['h'],
		]]);

		return $media;
	}
}
