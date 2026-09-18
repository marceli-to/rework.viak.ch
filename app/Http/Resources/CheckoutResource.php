<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Checkout;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Checkout
 */
class CheckoutResource extends JsonResource
{
	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'completed_at' => $this->completed_at?->toIso8601String(),
			'net' => $this->net,
			'discount_code' => $this->whenLoaded('discountCode', fn () => $this->discountCode->code),
			'discount_amount' => $this->discount_amount,

			// The draw-down, exposed because it is the one figure a customer
			// cannot work out for themselves: their code was applied to the
			// order, and it lands on invoices as they are raised
			// ([[06-bookings]]).
			'discount_remaining' => $this->remainingDiscount(),

			'bookings' => BookingResource::collection($this->whenLoaded('bookings')),
		];
	}
}
