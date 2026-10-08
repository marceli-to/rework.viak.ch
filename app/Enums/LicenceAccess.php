<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * *Lizenzzugriff* in the client's list ([[05-licences]]): tied to a person or
 * a machine (named, node-locked), shared from a licence server (floating), or
 * either, as every McNeel licence is. Display only.
 */
enum LicenceAccess: string
{
	case Named = 'named';
	case Floating = 'floating';
	case Either = 'either';

	public function label(): string
	{
		return match ($this) {
			self::Named => 'named',
			self::Floating => 'floating',
			self::Either => 'named oder floating',
		};
	}
}
