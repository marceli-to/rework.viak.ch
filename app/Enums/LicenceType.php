<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * *Lizenztyp* in the client's list ([[05-licences]]): bought once, or rented by
 * the year. A demo has none. Display only; nothing computes with it, since a
 * licence is history and not a record with a lifespan.
 */
enum LicenceType: string
{
	case Perpetual = 'perpetual';
	case Subscription = 'subscription';

	public function label(): string
	{
		return match ($this) {
			self::Perpetual => 'Dauerlizenz',
			self::Subscription => 'Jahresmietlizenz',
		};
	}
}
