<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where an account's licences are sent (#46, [[13-checkout]]): empty means the
 * account's own address. Asked at the checkout's address step when the basket
 * holds a licence, and copied onto each order as it is placed.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table): void {
			$table->string('licence_email')->nullable()->after('email');
		});
	}

	public function down(): void
	{
		Schema::table('users', function (Blueprint $table): void {
			$table->dropColumn('licence_email');
		});
	}
};
