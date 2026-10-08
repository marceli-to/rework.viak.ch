<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\LicenceOrder;
use Illuminate\Database\Eloquent\Builder;

/** The next licence order number ([[05-licences]]): six digits, as bookings and invoices ([[SequentialNumber]]). */
final class LicenceOrderNumber extends SequentialNumber
{
	protected function query(): Builder
	{
		return LicenceOrder::query();
	}
}
