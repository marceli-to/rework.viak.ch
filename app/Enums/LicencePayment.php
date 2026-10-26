<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\User;

/**
 * How a basket's licences are paid at checkout ([[13-checkout]]). Courses
 * are invoiced on confirmation either way.
 *
 * - `Card`: Stripe's page straight after *Bestellen*, as built 2026-10-20;
 * - `Invoice`: the order and its invoice are placed the same, and the
 *   customer pays the invoice later (QR bill, or the card link in the mail
 *   and on the profile), as an abandoned card payment already leaves it
 *   (#45). Asked for by the client on 2026-10-26.
 *
 * **The customer chooses**, on the payment step (#58, answered 2026-10-26).
 */
enum LicencePayment: string
{
	case Card = 'card';
	case Invoice = 'invoice';

	/**
	 * What this customer may choose: both, for everyone (#58). Kept as the
	 * one place a per-customer rule would go, should one ever be wanted.
	 *
	 * @return list<self>
	 */
	public static function offeredTo(?User $user): array
	{
		return self::cases();
	}
}
