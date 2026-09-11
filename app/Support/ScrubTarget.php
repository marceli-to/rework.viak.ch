<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Decides whether a database is an acceptable target for `db:scrub`.
 *
 * Split out from the command so the rule that stands between the tool and a
 * production database is covered by tests rather than by care.
 */
final class ScrubTarget
{
	/** A scrubbed database must never be mistakeable for the real one. */
	public const MARKERS = ['legacy', 'local', 'copy', 'dev', 'test', 'scrub'];

	public static function isObviouslyACopy(string $database): bool
	{
		$needle = mb_strtolower($database);

		foreach (self::MARKERS as $marker) {
			if (str_contains($needle, $marker)) {
				return true;
			}
		}

		return false;
	}
}
