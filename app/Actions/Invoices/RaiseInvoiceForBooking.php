<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\InvoiceItemType;
use App\Models\Booking;
use App\Models\Invoice;
use App\Support\InvoiceLine;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Bills one booking ([[03-invoices]]).
 *
 * **Invoices are raised when an event is confirmed, not when a seat is
 * booked.** A booking is a commitment; an event runs once it has the numbers,
 * and only then is there something to charge for. Measured across the 539
 * non-rental legacy invoices with a booking: 430 were dated after their
 * booking, mean lag 27.7 days, longest 209.
 *
 * So this action has two callers, both of them the same rule seen from a
 * different side: an event being confirmed (every seat on it), and — once
 * checkout exists — a seat sold on an event that is *already* confirmed. The
 * 109 legacy invoices dated the same day as their booking are all the second
 * case.
 *
 * One booking produces **one** invoice, with a course line and, where the
 * student took one, a laptop rental line. Legacy issued two separate invoices
 * with two numbers and two QR bills for that, because `is_rental` was a
 * boolean on the invoice and a document could only be one thing.
 *
 * ## Where the discount comes from, and where it will come from
 *
 * Today: the amount frozen on the booking, clamped at the fee.
 *
 * Chunk 06 decided on 2026-09-17 — after this was built — that **a code
 * discounts the order, not each course**: a completed checkout becomes a row
 * carrying the code and the amount computed against the whole basket, and each
 * invoice raised from that checkout **consumes what is left, capped at its own
 * net**, with the remainder carrying to the next one. Once that row exists,
 * this action asks it how much is left instead of reading the booking's frozen
 * copy; nothing else here changes, because a per-line `discount` with a cap at
 * the line's net is exactly what a draw-down needs ([[06-bookings]]).
 */
class RaiseInvoiceForBooking
{
	public function __construct(private readonly IssueInvoice $issue) {}

	/**
	 * Returns the invoice, or null where there is nothing to bill.
	 *
	 * Safe to call twice: a booking that already has a live invoice is left
	 * alone. That matters because confirmation is re-entrant — an event can be
	 * confirmed, gain a booking, and be confirmed again — and because legacy's
	 * equivalent (`findOrCreateFromBooking`) leaned on the same guard.
	 */
	public function execute(Booking $booking): ?Invoice
	{
		$booking->loadMissing('event.course', 'event.dates', 'user');

		if ($booking->isCancelled() || $booking->isInvoiced()) {
			return null;
		}

		// A free event bills nothing at all — not an invoice for 0.00. Legacy
		// made the same distinction, and it is a real one: a course given away
		// has no document, while a course paid for with a 100 % discount code
		// does. 16 historical invoices have a grand total of 0.00 for exactly
		// that second reason.
		if ($booking->event->free_of_charge) {
			return null;
		}

		return $this->issue->execute(
			user: $booking->user,
			lines: $this->lines($booking),
			dueAt: $this->dueAt($booking),
			invoiceAddress: $booking->invoice_address,
		);
	}

	/** @return array<int, InvoiceLine> */
	private function lines(Booking $booking): array
	{
		$event = $booking->event;

		$lines = [new InvoiceLine(
			type: InvoiceItemType::Course,
			description: $event->course->title.', '.$event->dateRange(),
			net: (string) $booking->course_fee,
			discount: $this->discountFor($booking),
			reference: $event->number(),
			itemable: $booking,
		)];

		if (! $booking->has_rental) {
			return $lines;
		}

		// The rental price is the one frozen on the booking, never today's
		// config value: the invoice is raised weeks after the seat was sold,
		// and a rate change in between must not reach a customer who was
		// quoted the old price.
		if ((string) $booking->rental_fee === '0.00') {
			throw new RuntimeException(
				"Booking {$booking->number} has a rental with no frozen price. Billing 0.00 would give the laptop away and billing today's rate would overcharge; neither is this action's call."
			);
		}

		$lines[] = new InvoiceLine(
			type: InvoiceItemType::Rental,
			description: 'Laptopmiete',
			net: (string) $booking->rental_fee,
			reference: $event->number(),
			itemable: $booking,
		);

		return $lines;
	}

	/**
	 * The discount this line may take, never more than the line is worth.
	 *
	 * Legacy clamped nothing, and it shows: booking 000512 spent a fixed CHF
	 * 648 code on a CHF 499 course and produced **invoice 000419 with a grand
	 * total of −149.00, still OPEN in the live books** — a course that owes the
	 * customer money. It is the only one of 710 bookings where the discount
	 * exceeds the fee.
	 *
	 * Capping here makes that unrepresentable rather than guarded against: the
	 * invoice lands at 0.00 and the unusable remainder stays behind, which is
	 * also precisely how chunk 06's draw-down behaves when a checkout's
	 * discount is larger than the first invoice raised from it.
	 */
	private function discountFor(Booking $booking): string
	{
		$fee = (string) $booking->course_fee;
		$discount = (string) $booking->discount_amount;

		return bccomp($discount, $fee, 2) > 0 ? $fee : $discount;
	}

	/**
	 * Ten days to pay, and the money in before the course runs.
	 *
	 * The later of `today + payment_period` and `event date - payment_period`,
	 * which is what the last change on the legacy branch was reaching for —
	 * "set invoice payment deadline to 10 days before event start" — with the
	 * floor it needed: an event confirmed a week before it runs would
	 * otherwise be invoiced with a deadline already in the past.
	 *
	 * That change never had any effect in production, because `due_at` rewrote
	 * itself to the current timestamp on every write. See the migration.
	 */
	private function dueAt(Booking $booking): Carbon
	{
		$period = (int) config('invoice.payment_period');

		$floor = today()->addDays($period);
		$beforeEvent = $booking->event->date->copy()->subDays($period);

		return $beforeEvent->greaterThan($floor) ? $beforeEvent : $floor;
	}
}
