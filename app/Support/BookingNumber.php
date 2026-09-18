<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;

/**
 * The next booking number ([[06-bookings]]).
 *
 * Six digits, one continuous sequence, never reused — the same rules invoices
 * follow, and now literally the same code ([[SequentialNumber]]).
 *
 * Legacy minted these with a full-table load of all 710 bookings and a window in
 * which two checkouts in the same second could take the same number. Chunk 03
 * fixed that for invoices and left bookings carrying the original; this closes
 * it.
 */
final class BookingNumber extends SequentialNumber
{
	protected function query(): Builder
	{
		return Booking::withTrashed();
	}
}
