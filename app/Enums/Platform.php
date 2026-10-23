<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a licence runs on ([[05-licences]]).
 */
enum Platform: string
{
	case Windows = 'windows';
	case MacOS = 'macos';

	public function label(): string
	{
		return match ($this) {
			self::Windows => 'Windows',
			self::MacOS => 'macOS',
		};
	}
}
