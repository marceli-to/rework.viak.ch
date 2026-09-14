<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What the student brings to the course, so the office knows whether to
 * prepare a rental laptop. A student may tick more than one, which is why
 * `users.operating_systems` is a JSON array rather than a column.
 *
 * Legacy stored the same multi-select as a CSV string, so "macOS,Windows" and
 * "Windows,macOS" were two different values to anyone filtering on it.
 */
enum OperatingSystem: string
{
	case MacOS = 'macos';
	case Windows = 'windows';
	case Other = 'other';

	/** Legacy wrote the display label straight into the column. */
	public static function fromLegacyLabel(string $label): ?self
	{
		return match (mb_strtolower(trim($label))) {
			'macos' => self::MacOS,
			'windows' => self::Windows,
			'anderes' => self::Other,
			default => null,
		};
	}

	public function label(): string
	{
		return match ($this) {
			self::MacOS => 'macOS',
			self::Windows => 'Windows',
			self::Other => __('anderes'),
		};
	}
}
