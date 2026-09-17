<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Models\Event;
use App\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bills every seat on a confirmed event ([[RaiseInvoiceForBooking]]).
 *
 * One invoice per booking, not one per event: each student gets their own
 * document, addressed to their own billing address.
 *
 * Failures are collected rather than thrown. A confirmation is a decision
 * about a course — it has already been made, and the students have already
 * been told — so one booking with bad data must not stop the other twelve from
 * being billed, and must not leave the event half-confirmed. Every invoice is
 * written in its own transaction and the action is re-entrant, so running it
 * again after fixing the data picks up exactly what is missing.
 */
class RaiseInvoicesForEvent
{
	public function __construct(private readonly RaiseInvoiceForBooking $raise) {}

	/** @return Collection<int, Invoice> */
	public function execute(Event $event): Collection
	{
		$invoices = collect();

		foreach ($event->bookings()->active()->with('user', 'event.course', 'event.dates')->get() as $booking) {
			try {
				if ($invoice = $this->raise->execute($booking)) {
					$invoices->push($invoice);
				}
			} catch (Throwable $e) {
				// Reported, not swallowed: this is the admin's work queue, and
				// an unbilled seat on a course that is going ahead is money.
				Log::error("Could not invoice booking {$booking->number} on event {$event->uuid}: ".$e->getMessage());
			}
		}

		return $invoices;
	}
}
