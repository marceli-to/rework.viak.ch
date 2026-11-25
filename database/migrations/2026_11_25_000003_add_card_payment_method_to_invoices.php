<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * **TWINT beside the card** (Marcel, 2026-11-25, [[13-checkout]]): which method
 * paid an invoice on Stripe's page, `card` or `twint`, so the receipt, the
 * office mail and the memo in the books name it. Null: not paid online, or
 * paid before this column, which was always a card.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('invoices', function (Blueprint $table) {
			$table->string('card_payment_method')->nullable()->after('card_payment');
		});
	}

	public function down(): void
	{
		Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('card_payment_method'));
	}
};
