<?php

declare(strict_types=1);

/*
 * Invoicing constants ([[03-invoices]]).
 *
 * The legacy config file had 12 keys, most of them QR-bill and beneficiary
 * details that belong with the PDF rendering rather than here. This is what the
 * domain needs to raise an invoice; the rest arrives with the document.
 */

return [

	/*
	 * Standard Swiss VAT, applied on top of a net price and rounded to the
	 * centime — not to 0.05. Legacy's `round($x * 20) / 20` was the cash
	 * rounding habit applied a step too early; the shop, which computed
	 * 370.00 → 29.97, was right. Courses are exempt regardless of this
	 * number: see [[InvoiceItemType]].
	 */
	'vat_rate' => env('INVOICE_VAT_RATE', 8.1),

	/*
	 * Days a customer has to pay. The deadline is the later of
	 * `today + payment_period` and `event date - payment_period`, so money
	 * for a course arrives before the course runs, and a late-confirmed event
	 * never produces a deadline in the past.
	 */
	'payment_period' => 10,

	/*
	 * CHF 80 laptop for students without a suitable machine. Still here
	 * because it is the price a *new* rental is sold at; once sold it is
	 * frozen on the booking as `rental_fee`, so a change to this number
	 * cannot reach a booking that has already been made.
	 */
	'rental_fee' => 80.00,

	/*
	 * Late-cancellation penalty ([[06-bookings]]).
	 *
	 * Days counted from today to the event: inside `penalty_full_days` the
	 * student owes the whole fee, inside `penalty_half_days` half of it, and
	 * earlier than that nothing. The legacy numbers, unchanged — the production
	 * data confirms the rule fired at the right rate all fourteen times it
	 * applied, so this is not a place to improve anything.
	 *
	 * The boundaries are worth real money and the failure is silent, so both are
	 * pinned by tests ([[CancellationPenalty]]).
	 */
	'penalty_full_days' => 11,
	'penalty_half_days' => 20,

];
