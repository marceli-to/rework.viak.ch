<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\LicenceOrder;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A customer ordered licences in the shop ([[13-checkout]]). Not fired for an
 * order VIAK enters on the dashboard, whose invoice VIAK sends itself
 * ([[05-licences]]).
 */
class LicenceOrderPlaced
{
	use Dispatchable;

	public function __construct(public readonly LicenceOrder $order) {}
}
