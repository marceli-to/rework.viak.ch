<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'number' => $this->number,

			// What the seat was *offered* at. What was finally *charged* is the
			// invoice's business, and the two legitimately disagree — six
			// historical bookings were invoiced at half their course_fee under
			// an arrangement recorded only on the invoice ([[03-invoices]]).
			'course_fee' => $this->course_fee,
			'has_rental' => $this->has_rental,
			'rental_fee' => $this->rental_fee,

			'booked_at' => $this->booked_at?->toIso8601String(),
			'cancelled_at' => $this->cancelled_at?->toIso8601String(),
			'cancellation_reason' => $this->cancellation_reason?->value,
			'is_cancelled' => $this->isCancelled(),

			// Whether the laptop can still be added or dropped for free, which
			// is exactly "before the invoice is raised" ([[SetRental]]).
			'is_editable' => $this->isEditable(),

			'event' => new EventResource($this->whenLoaded('event')),
		];
	}
}
