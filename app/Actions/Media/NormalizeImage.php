<?php

declare(strict_types=1);

namespace App\Actions\Media;

use Imagick;
use Throwable;

/**
 * Shrinks an oversized source in place ([[08-accounts]]).
 *
 * Ported from `forrerzimmermann.ch`. Sources above 3200 px on the longest edge
 * buy nothing — the largest variant the component renders is 1920 — and cost
 * disk and render time on every first request.
 *
 * ## The trap this returns a value for
 *
 * **Crop coordinates are in source pixels.** Rescale the source and every crop
 * on it points somewhere else — the image still renders, just wrong, which is
 * the kind of failure nobody notices. Measured on the legacy data: **29 of the
 * 407 cropped images come from sources wider than 3200**, so this is not
 * hypothetical.
 *
 * Hence the return value: the scale factor applied, `1.0` when nothing changed,
 * `null` on failure. Whatever holds a crop for this file must multiply by it.
 * [[PortMedia]] does; so must anything else that calls this on a file that
 * already has one.
 *
 * ## Orientation
 *
 * A phone stores a portrait photo as landscape pixels plus an EXIF flag saying
 * "rotate 90° clockwise". Browsers honour the flag; stripping the metadata
 * throws it away and leaves the photo on its side. So the rotation is baked
 * into the pixels first — and a file with the flag set is rewritten even when
 * it is small enough to keep, because Glide and the cropper would otherwise
 * disagree with the stored width and height about which way is up.
 *
 * A rotation moves a crop just as a rescale does, and this does not correct
 * for it. It does not need to: uploads have no crop yet, and **none of the
 * 371 legacy sources carries a flag other than upright** (257 upright, 114
 * without EXIF), so [[PortMedia]] never hits it.
 */
class NormalizeImage
{
	public const MAX_EDGE = 3200;

	private const NORMALIZABLE = ['image/jpeg', 'image/png', 'image/webp'];

	public function execute(string $absolutePath, ?string $mimeType): ?float
	{
		if (! in_array($mimeType, self::NORMALIZABLE, true) || ! is_file($absolutePath)) {
			return $mimeType === null ? null : 1.0;
		}

		$size = @getimagesize($absolutePath);

		if (! $size || ! $size[0] || ! $size[1]) {
			return null;
		}

		$longest = max($size[0], $size[1]);

		if ($longest <= self::MAX_EDGE && $this->isUpright($absolutePath)) {
			return 1.0;
		}

		try {
			$image = new Imagick($absolutePath);

			// Before the strip below, which is what would lose the flag.
			$image->autoOrient();
			$image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);

			// Strip metadata but keep the colour profile. Without the profile a
			// photograph shot in Adobe RGB comes out visibly flat, which looks
			// like a bad camera rather than a bad pipeline.
			$profiles = $image->getImageProfiles('icc', true);
			$image->stripImage();

			if (! empty($profiles['icc'])) {
				$image->profileImage('icc', $profiles['icc']);
			}

			if ($longest > self::MAX_EDGE) {
				$image->getImageWidth() >= $image->getImageHeight()
					? $image->thumbnailImage(self::MAX_EDGE, 0)
					: $image->thumbnailImage(0, self::MAX_EDGE);
			}

			$image->setImageCompressionQuality(90);
			$image->writeImage($absolutePath);
			$image->clear();

			clearstatcache(true, $absolutePath);
		} catch (Throwable $e) {
			report($e);

			return null;
		}

		return min(1.0, self::MAX_EDGE / $longest);
	}

	/** Read from the header alone; a missing or unreadable flag counts as upright. */
	private function isUpright(string $absolutePath): bool
	{
		try {
			$image = new Imagick;
			$image->pingImage($absolutePath);
			$orientation = $image->getImageOrientation();
			$image->clear();
		} catch (Throwable) {
			return true;
		}

		return in_array($orientation, [Imagick::ORIENTATION_UNDEFINED, Imagick::ORIENTATION_TOPLEFT], true);
	}
}
