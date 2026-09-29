<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Enums\DiscountType;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Support\DiscountCodeGenerator;

/**
 * Money already paid for a seat that was given up, as a discount code for the
 * next booking (Marcel, 2026-09-29: legacy's behaviour, back). The mail names
 * it and offers a refund instead ([[10-mail]]).
 *
 * Legacy's terms: a fixed amount, valid from today for a year. **One change:
 * it is good once.** Legacy's rule made any code with dates unlimited while
 * valid (`DiscountCode::isSingle()`), so a credit could be spent again and
 * again for a year; here `usage_limit` is 1.
 *
 * Idempotent: a seat is credited once, whatever calls this twice.
 */
class IssueCreditCode
{
	public function __construct(private readonly DiscountCodeGenerator $codes) {}

	public function execute(Booking $booking, string $amount): DiscountCode
	{
		return DiscountCode::query()->where('booking_id', $booking->getKey())->first()
			?? DiscountCode::create([
				'code' => $this->codes->next(),
				'type' => DiscountType::Fixed,
				'amount' => $amount,
				'usage_limit' => 1,
				'valid_from' => today(),
				'valid_to' => today()->addYear(),
				'remarks' => "Gutschrift für Buchung {$booking->number}",
				'booking_id' => $booking->getKey(),
			]);
	}
}
