<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a checkout leaves behind ([[06-bookings]]).
 *
 * Legacy's basket lived in the session and evaporated at checkout. Each booking
 * kept its own copy of the code and the amount, and nothing recorded that two
 * bookings had been one purchase — which is why a fixed CHF 50 code took 50 off
 * the basket screen and 50 off *each* booking. Three real baskets gave away
 * CHF 80 that way.
 *
 * Marcel decided on 2026-09-17 that **a code discounts the order**. That needs a
 * thing for the discount to be level with, so a completed checkout becomes a
 * row, and bookings point at it.
 *
 * This does **not** reopen chunk 03's rejection of Order/OrderItem. That was
 * about what invoices hang off, and it stands — invoices are still raised when
 * an event is confirmed, never from a checkout. This row is the record of what
 * was agreed at the till; nothing is invoiced *from* it, it is read *by*
 * whatever is being invoiced.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('checkouts', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();

			$table->foreignId('user_id')->constrained()->restrictOnDelete();

			// The code as it was at the instant of checkout, and what it was
			// worth against the *whole* basket. Null on a checkout without one,
			// which is 89 % of them historically.
			$table->foreignId('discount_code_id')->nullable()->constrained()->nullOnDelete();
			$table->decimal('discount_amount', 8, 2)->default(0);

			// What the basket was worth before the discount. Not used to bill
			// anything — invoices are raised per confirmed event, from the
			// event's own fee — but without it there is no record of what the
			// customer was shown, and the discount cannot be audited.
			$table->decimal('net', 8, 2)->default(0);

			// Where the bill should go, as chosen at the till. Copied onto each
			// booking, because an invoice raised weeks later must not follow a
			// later edit to the user's address.
			$table->json('invoice_address')->nullable();

			$table->timestamp('completed_at');

			$table->timestamps();

			$table->index(['user_id', 'completed_at']);
		});

		Schema::table('bookings', function (Blueprint $table) {
			// Null for the 710 ported bookings and for anything an admin
			// creates by hand — neither went through a till.
			$table->foreignId('checkout_id')->nullable()->after('user_id')
				->constrained()->nullOnDelete();

			// Legacy had no such column. It distinguished a student cancelling
			// from VIAK calling off a course only by *which function ran*:
			// `Booking::cancel()` applied the penalty, `EventCancelledHandler`
			// flagged the rows directly and never called it. Nothing stated the
			// rule, and 115 of the 143 bookings on VIAK-cancelled courses fall
			// inside the 100 % penalty window — so if the two paths had ever
			// been merged, every one of those students would have been invoiced
			// in full for a course VIAK itself called off ([[BookingCancellationReason]]).
			$table->string('cancellation_reason', 20)->nullable()->after('cancelled_at');
		});

		Schema::table('discount_codes', function (Blueprint $table) {
			// Legacy derived "single use" from *having no validity window*:
			//
			//   isSingle() { return $this->valid_from && $this->valid_to ? FALSE : TRUE; }
			//
			// So "how often may this be used" and "when is it valid" were the
			// same two columns and neither could be set on its own. The 107
			// codes split exactly along that line — 35 with no dates, 72 with
			// both, none with one — which is what a rule looks like when it is
			// really an accident. Null here means unlimited.
			$table->unsignedInteger('usage_limit')->nullable()->after('amount');
		});

		// 17 rows in three years, so this gets a table and nothing else: no
		// model of its own beyond the pivot, no Action layer, no place in the
		// dashboard's navigation. Kept because Marcel asked for it kept, and
		// because `Booking::create()` already clears one when the course is
		// booked — a saved course you have since booked is noise.
		Schema::create('bookmarks', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->foreignId('event_id')->constrained()->cascadeOnDelete();
			$table->timestamps();

			$table->unique(['user_id', 'event_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('bookmarks');

		Schema::table('discount_codes', function (Blueprint $table) {
			$table->dropColumn('usage_limit');
		});

		Schema::table('bookings', function (Blueprint $table) {
			$table->dropConstrainedForeignId('checkout_id');
			$table->dropColumn('cancellation_reason');
		});

		Schema::dropIfExists('checkouts');
	}
};
