<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A cancelled licence order, when and by whom ([[CancelLicenceOrder]]). */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('licence_orders', function (Blueprint $table): void {
			$table->timestamp('cancelled_at')->nullable()->after('paid_at');
			$table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
		});
	}

	public function down(): void
	{
		Schema::table('licence_orders', function (Blueprint $table): void {
			$table->dropConstrainedForeignId('cancelled_by');
			$table->dropColumn('cancelled_at');
		});
	}
};
