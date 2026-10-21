<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\LicenceOrder;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Licences were ordered: by a customer in the shop ([[13-checkout]]), or by
 * VIAK on the dashboard for one ([[05-licences]]); the order's `entered_by`
 * tells the two apart.
 */
class LicenceOrderPlaced
{
	use Dispatchable;

	public function __construct(public readonly LicenceOrder $order) {}
}
