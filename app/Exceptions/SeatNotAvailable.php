<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Event;
use RuntimeException;

/**
 * The seat is not there any more ([[06-bookings]]).
 *
 * Legacy checked for a duplicate booking and nothing else, so a basket left open
 * while a course filled up, or while VIAK called it off, still sold a seat at
 * checkout. Capacity and state are re-checked at the moment of sale, not at the
 * moment of browsing.
 */
class SeatNotAvailable extends RuntimeException
{
	private function __construct(public readonly Event $event, string $message)
	{
		parent::__construct($message);
	}

	public static function full(Event $event): self
	{
		return new self($event, 'This course is fully booked.');
	}

	public static function closed(Event $event): self
	{
		return new self($event, 'This course is no longer open for bookings.');
	}

	/**
	 * Every laptop was rented while the basket stood open. **In German**, unlike
	 * the three above: it reaches the customer as it is, in the summary page's
	 * toast.
	 */
	public static function noRentalLeft(Event $event): self
	{
		return new self($event, 'Für diesen Kurs sind keine Mietcomputer mehr verfügbar.');
	}

	public static function alreadyBooked(Event $event): self
	{
		return new self($event, 'You already have a place on this course.');
	}
}
