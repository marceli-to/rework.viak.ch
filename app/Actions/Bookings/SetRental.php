<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Models\Booking;
use RuntimeException;

/**
 * Adds or drops the CHF 80 laptop, while that is still free ([[06-bookings]]).
 *
 * The window is *before the invoice is raised*, and it is the only window in
 * which the rental can change without money moving. Legacy's `addRental` /
 * `cancelRental` mutated a `has_rental` tinyint and then reached into the
 * invoice layer to delete a document — because it billed the laptop as its own
 * separate invoice, with its own number and its own QR bill.
 *
 * Chunk 03 made the rental a **line** on the booking's invoice, and invoices are
 * raised at confirmation. So the reach into the invoice layer disappears: either
 * the invoice does not exist yet and this is free, or it does and this refuses.
 *
 * `rentals_available` is the event's own switch — a course in a room without
 * machines cannot sell a laptop.
 */
class SetRental
{
	public function execute(Booking $booking, bool $rental): Booking
	{
		if (! $booking->isEditable()) {
			throw new RuntimeException("Booking {$booking->number} has already been invoiced; the laptop rental cannot be changed without reissuing the invoice, which is an admin's decision and not this action's.");
		}

		if ($rental && ! $booking->event->rentals_available) {
			throw new RuntimeException("Event {$booking->event->number()} does not offer laptop rental.");
		}

		$booking->forceFill([
			'has_rental' => $rental,
			// Frozen at today's price when added, cleared when dropped. Never
			// read from config at invoice time: the invoice is raised a mean
			// 27.7 days later and a rate change must not reach a customer who
			// was quoted the old price.
			'rental_fee' => $rental
				? number_format((float) config('invoice.rental_fee'), 2, '.', '')
				: '0.00',
		])->save();

		return $booking;
	}
}
