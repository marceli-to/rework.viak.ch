<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * *Mietlizenzen, die bald auslaufen* (#83, 2026-11-25): when VIAK last sent a
 * line's renewal reminder, and who ([[RentalRenewals]], `05-licences.md`).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('licence_order_items', function (Blueprint $table) {
			$table->timestamp('reminded_at')->nullable()->after('dispatched_by');
			$table->foreignId('reminded_by')->nullable()->after('reminded_at')->constrained('users')->nullOnDelete();
		});
	}

	public function down(): void
	{
		Schema::table('licence_order_items', function (Blueprint $table) {
			$table->dropConstrainedForeignId('reminded_by');
			$table->dropColumn('reminded_at');
		});
	}
};
