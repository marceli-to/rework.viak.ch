<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Freezes the laptop rental price on the booking, the way `course_fee` is
 * already frozen ([[03-invoices]]).
 *
 * Legacy kept `has_rental` as a bare boolean and read the price from
 * `config('invoice.cost_rental')` at the moment the invoice was raised — which,
 * because invoices are raised on confirmation and not at checkout, is a mean
 * 27.7 days after the booking and up to 209. A rate change inside that window
 * would bill a customer more than they were quoted, which is exactly the bug
 * `course_fee` exists to prevent, on the same row.
 *
 * It has never bitten: all 40 historical `has_rental` bookings resolve to
 * CHF 80. That is luck, not design.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('bookings', function (Blueprint $table) {
			// What the rental was sold for, captured at booking time. Zero on
			// a booking without a rental — `has_rental` stays the flag.
			$table->decimal('rental_fee', 8, 2)->default(0)->after('has_rental');
		});
	}

	public function down(): void
	{
		Schema::table('bookings', function (Blueprint $table) {
			$table->dropColumn('rental_fee');
		});
	}
};
