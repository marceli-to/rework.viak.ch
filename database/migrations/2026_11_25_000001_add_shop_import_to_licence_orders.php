<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orders brought over from 3d-software.ch ([[PortShop]], `05-licences.md`,
 * *The old shop's orders*). The shop raised its own invoices, so an imported
 * order has none here, and three things stand in for what the invoice says:
 *
 * - **`shop_order_id`**: the Shopware order's id, hex. Marks the order as
 *   imported (its payment is `paid_at` alone, never "free" for lacking an
 *   invoice) and is what a rerun deletes by.
 * - **`total`**: what the shop charged, VAT included, shown where an invoice's
 *   grand total would be.
 * - **`comment`**: the customer's note on the order, which sometimes holds a
 *   licence key or another delivery address.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('licence_orders', function (Blueprint $table) {
			$table->char('shop_order_id', 32)->nullable()->unique()->after('number');
			$table->decimal('total', 10, 2)->nullable()->after('paid_at');
			$table->text('comment')->nullable()->after('total');
		});
	}

	public function down(): void
	{
		Schema::table('licence_orders', function (Blueprint $table) {
			$table->dropUnique(['shop_order_id']);
			$table->dropColumn(['shop_order_id', 'total', 'comment']);
		});
	}
};
