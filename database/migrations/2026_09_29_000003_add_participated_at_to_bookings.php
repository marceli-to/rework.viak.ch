<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance: when an admin ticked the seat as attended ([[10-mail]]).
 * Legacy's `hasParticipated` flag, as the time it was set. Only a ticked seat
 * gets the participation confirmation when the course closes (Marcel,
 * 2026-09-29: non-participants get none).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('bookings', function (Blueprint $table) {
			$table->timestamp('participated_at')->nullable()->after('cancelled_at');
		});
	}

	public function down(): void
	{
		Schema::table('bookings', function (Blueprint $table) {
			$table->dropColumn('participated_at');
		});
	}
};
