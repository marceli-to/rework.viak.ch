<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\SetRental;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\CancelBookingRequest;
use App\Http\Requests\Bookings\SetRentalRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends Controller
{
	/** A student's own seats. Admins read someone else's through the dashboard. */
	public function index(Request $request): AnonymousResourceCollection
	{
		$bookings = $request->user()->bookings()
			->with(['event.course', 'event.dates', 'event.location'])
			->orderByDesc('booked_at')
			->get();

		return BookingResource::collection($bookings);
	}

	public function show(Booking $booking): BookingResource
	{
		$this->authorize('view', $booking);

		return new BookingResource($booking->load(['event.course', 'event.dates', 'event.location']));
	}

	/**
	 * Gives up a seat, and charges for it where the rule says so.
	 *
	 * The response carries the penalty invoice when one was raised, because the
	 * student needs to be told at the moment they cancel rather than when the
	 * bill arrives. Legacy told them nothing here.
	 */
	public function cancel(CancelBookingRequest $request, Booking $booking, CancelBooking $cancel): BookingResource
	{
		$penalty = $cancel->execute($booking, $request->reason());

		return (new BookingResource($booking->refresh()->load(['event.course', 'event.dates'])))
			->additional(['penalty' => $penalty === null ? null : [
				'number' => $penalty->number,
				'grand_total' => $penalty->grand_total,
				'due_at' => $penalty->due_at?->toDateString(),
			]]);
	}

	/** Adds or drops the laptop, while that is still free ([[SetRental]]). */
	public function setRental(SetRentalRequest $request, Booking $booking, SetRental $setRental): BookingResource
	{
		$booking = $setRental->execute($booking, $request->rental());

		return new BookingResource($booking->load(['event.course', 'event.dates']));
	}
}
