<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registration no longer asks for *Betriebssystem* (decided 2026-10-23), and
 * nothing else read it, so the column goes with the field ([[01-schema]]).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('operating_systems');
		});
	}

	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->json('operating_systems')->nullable()->after('gender');
		});
	}
};
