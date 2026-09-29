<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the office was reminded to confirm or cancel a date ([[RemindUndecidedEvents]]).
 * Legacy's `hasCancelOrConfirmReminder` flag, as a time; the flag was not ported,
 * so each planned date inside the window is reminded once after cutover.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('events', function (Blueprint $table) {
			$table->timestamp('reminded_at')->nullable()->after('closed_at');
		});
	}

	public function down(): void
	{
		Schema::table('events', function (Blueprint $table) {
			$table->dropColumn('reminded_at');
		});
	}
};
