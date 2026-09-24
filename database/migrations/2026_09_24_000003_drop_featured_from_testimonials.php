<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A testimonial does not say where it appears (Marcel, 2026-09-24). The page
 * that shows testimonials picks them — the course form, and the homepage form
 * still to come — so the `featured` flag goes ([[07-dashboard]]).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('testimonials', function (Blueprint $table) {
			$table->dropColumn('featured');
		});
	}

	public function down(): void
	{
		Schema::table('testimonials', function (Blueprint $table) {
			$table->boolean('featured')->default(false)->after('context');
		});
	}
};
