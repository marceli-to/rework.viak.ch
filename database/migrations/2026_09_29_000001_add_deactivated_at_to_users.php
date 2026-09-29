<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An account is deactivated, never deleted (#16, [[07-dashboard]]): its
 * bookings, invoices and documents keep pointing at a real person, and the
 * person can no longer sign in. A timestamp, so it also says since when.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->timestamp('deactivated_at')->nullable()->after('email_verified_at');
		});
	}

	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('deactivated_at');
		});
	}
};
