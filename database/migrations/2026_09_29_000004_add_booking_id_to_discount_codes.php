<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The seat a credit code was issued for ([[IssueCreditCode]]): a paid invoice
 * on a cancelled booking becomes a code worth what was paid (Marcel,
 * 2026-09-29, legacy's behaviour). Null for every code an admin makes.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('discount_codes', function (Blueprint $table) {
			$table->foreignId('booking_id')->nullable()->after('usage_limit')->constrained()->nullOnDelete();
		});
	}

	public function down(): void
	{
		Schema::table('discount_codes', function (Blueprint $table) {
			$table->dropConstrainedForeignId('booking_id');
		});
	}
};
