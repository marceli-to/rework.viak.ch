<?php

declare(strict_types=1);

namespace App\Support;

use Imagick;

/**
 * Which image formats this server can actually produce ([[08-accounts]]).
 *
 * Asked rather than assumed: a `<picture>` offering AVIF on a box whose Imagick
 * was built without it serves a broken source to every browser that prefers it,
 * and the failure looks like a CDN problem rather than a build-flag problem.
 */
final class ImageFormats
{
	/** The widths `<x-media.image>` generates, and the only ones Glide will serve. */
	public const WIDTHS = [480, 640, 768, 1024, 1280, 1440, 1600, 1920];

	/** @var array<int, string>|null */
	private static ?array $supported = null;

	/** @return array<int, string> */
	public static function supported(): array
	{
		if (self::$supported !== null) {
			return self::$supported;
		}

		$formats = ['jpg', 'jpeg', 'png', 'gif'];

		if (class_exists(Imagick::class)) {
			if (Imagick::queryFormats('WEBP')) {
				$formats[] = 'webp';
			}
			if (Imagick::queryFormats('AVIF')) {
				$formats[] = 'avif';
			}
		}

		return self::$supported = $formats;
	}

	public static function supports(string $format): bool
	{
		return in_array(mb_strtolower($format), self::supported(), true);
	}

	public static function mimeFor(string $format): string
	{
		return match (mb_strtolower($format)) {
			'avif' => 'image/avif',
			'webp' => 'image/webp',
			'png' => 'image/png',
			'gif' => 'image/gif',
			default => 'image/jpeg',
		};
	}
}
