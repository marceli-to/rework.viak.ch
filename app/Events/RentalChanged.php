<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

/** A laptop was added to a seat, or dropped from it ([[SetRental]], [[10-mail]]). */
class RentalChanged
{
	use Dispatchable;

	public function __construct(
		public readonly Booking $booking,
		public readonly bool $added,
	) {}
}
