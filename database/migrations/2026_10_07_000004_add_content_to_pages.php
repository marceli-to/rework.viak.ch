<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A fixed page's editable copy ([[Page]]): the homepage's About teaser first
 * (Marcel, 2026-10-07), whose heading and text are edited in the dashboard.
 * One JSON object per page, its keys the page's form fields
 * ([[HomeAboutSchema]]); a page without editable copy leaves it null.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('pages', function (Blueprint $table) {
			$table->json('content')->nullable()->after('key');
		});
	}

	public function down(): void
	{
		Schema::table('pages', function (Blueprint $table) {
			$table->dropColumn('content');
		});
	}
};
