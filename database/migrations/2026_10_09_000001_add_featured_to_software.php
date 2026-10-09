<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * *Beliebte Angebote* takes software too (the review's homepage marker 7,
 * [[04-content]]): flagged in its form as a course is, and the homepage lists
 * the flagged courses and software in one grid (Marcel, 2026-10-09).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('software', function (Blueprint $table) {
			$table->boolean('featured')->default(false)->after('publish');
		});
	}

	public function down(): void
	{
		Schema::table('software', function (Blueprint $table) {
			$table->dropColumn('featured');
		});
	}
};
