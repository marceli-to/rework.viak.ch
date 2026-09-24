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

	/*
	 * **German, and each one names its course.** The message reaches the
	 * customer as it is — the toast on the summary page, the portal's toast —
	 * and until 2026-09-24 three of these were English (*This course is fully
	 * booked.*). A basket can hold several courses and the toast shows one
	 * message, so "diesen Kurs" would not say which.
	 *
	 * The wording is legacy's where legacy has one: *Kurs ist ausgebucht* on
	 * the event card, *Du hast bereits eine Buchung für diesen Kurs!* on the
	 * basket row. Legacy never refused a closed course at checkout, so that one
	 * is new.
	 */

	public static function full(Event $event): self
	{
		return new self($event, "«{$event->course->title}»: Kurs ist ausgebucht.");
	}

	public static function closed(Event $event): self
	{
		return new self($event, "«{$event->course->title}» kann nicht mehr gebucht werden.");
	}

	/** Every laptop was rented while the basket stood open. */
	public static function noRentalLeft(Event $event): self
	{
		return new self($event, "Für «{$event->course->title}» sind keine Mietcomputer mehr verfügbar.");
	}

	public static function alreadyBooked(Event $event): self
	{
		return new self($event, "Du hast bereits eine Buchung für «{$event->course->title}».");
	}
}
