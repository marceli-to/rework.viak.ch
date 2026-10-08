<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Licence orders ([[05-licences]]): what a licence has in place of a booking.
 * It freezes what was sold, carries the invoice raised for it, and is the
 * dispatch worklist's table, since a licence is delivered by a person at VIAK
 * ordering it from the reseller and forwarding it by mail.
 *
 * - **A line freezes its title, article number and unit price**, as a booking
 *   freezes `course_fee`: the catalogue changes, the order must not.
 *   `licence_variant_id` is nullable: **the free line** (#36) is a typed title
 *   and price with no variant behind it.
 * - **`quantity`** on the line: the Teams licences are sold three at a time.
 * - **`host`**: the host software a plugin is ordered for (Maxwell V5,
 *   RealFlow Plugin), chosen from the product's list and frozen.
 * - **Dispatch is per line** (`dispatched_at`, `dispatched_by`): one order can
 *   hold licences from two makers, ordered from two resellers on two days.
 *   `dispatched_by` answers "who sent it, and when" when one never arrived.
 * - **`invoice_id`** on the order: a priced order raises one invoice at once,
 *   a licence having no confirmation to wait for. A free one (demos, #35)
 *   raises none and is `paid_at` when placed.
 * - **`delivery_email`**: where the licences go, empty meaning the account's
 *   own address (#46, `13-checkout.md`).
 * - **`entered_by`**: the admin who typed in an order taken by mail or phone;
 *   null for one placed in the shop.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('licence_orders', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->string('number', 6)->unique();
			$table->foreignId('user_id')->constrained()->restrictOnDelete();
			$table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
			$table->json('invoice_address')->nullable();
			$table->string('delivery_email')->nullable();
			$table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
			$table->timestamp('paid_at')->nullable();
			$table->timestamps();
		});

		Schema::create('licence_order_items', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->foreignId('licence_order_id')->constrained()->cascadeOnDelete();
			$table->foreignId('licence_variant_id')->nullable()->constrained()->nullOnDelete();
			$table->string('title');
			$table->string('sku', 32)->nullable();
			$table->string('host')->nullable();
			$table->decimal('price', 10, 2);
			$table->unsignedSmallInteger('quantity')->default(1);
			$table->unsignedSmallInteger('position')->default(1);
			$table->timestamp('dispatched_at')->nullable();
			$table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
			$table->timestamps();

			$table->index(['licence_order_id', 'position']);
			$table->index('dispatched_at');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('licence_order_items');
		Schema::dropIfExists('licence_orders');
	}
};
