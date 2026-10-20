<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The card payment that settled an invoice ([[13-checkout]], #28): Stripe's
 * payment id. It is what makes [[RecordCardPayment]] safe to call twice, from
 * the return page and from the webhook, and what tells a second payment of the
 * same invoice from a repeat of the first.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('invoices', function (Blueprint $table): void {
			$table->string('card_payment')->nullable()->after('paid_at');
		});
	}

	public function down(): void
	{
		Schema::table('invoices', function (Blueprint $table): void {
			$table->dropColumn('card_payment');
		});
	}
};
