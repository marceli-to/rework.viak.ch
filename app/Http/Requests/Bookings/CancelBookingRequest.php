<?php

declare(strict_types=1);

namespace App\Http\Requests\Bookings;

use App\Enums\BookingCancellationReason;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Giving up a seat ([[06-bookings]]).
 *
 * The reason is **not** taken from the request. A student cancelling their own
 * booking cancels as a student; an admin cancelling someone else's cancels as an
 * administrator. Letting the client name the reason would let it choose whether
 * to be charged.
 *
 * `EventCancelled` is never reachable here at all — that reason belongs to
 * [[CancelBookingsForEvent]], which runs when VIAK calls a course off.
 */
class CancelBookingRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->can('cancel', $this->route('booking')) ?? false;
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [];
	}

	public function reason(): BookingCancellationReason
	{
		$booking = $this->route('booking');

		return $this->user()->id === $booking->user_id
			? BookingCancellationReason::Student
			: BookingCancellationReason::Administrator;
	}
}
