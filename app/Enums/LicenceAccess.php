<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * *Lizenzzugriff* in the client's list ([[05-licences]]): tied to a person or
 * a machine (named, node-locked), shared from a licence server (floating), or
 * either, as every McNeel licence is. Display only.
 *
 * Labelled *Einzelplatz* / *Netzwerk* as the software mockup words it, with
 * the vendors' term in brackets (Marcel, 2026-10-08: named / floating alone
 * meant nothing to him, and will mean less to a customer).
 */
enum LicenceAccess: string
{
	case Named = 'named';
	case Floating = 'floating';
	case Either = 'either';

	public function label(): string
	{
		return match ($this) {
			self::Named => 'Einzelplatz (named)',
			self::Floating => 'Netzwerk (floating)',
			self::Either => 'Einzelplatz oder Netzwerk',
		};
	}
}
