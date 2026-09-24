<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a testimonial is **about** — a course, a software, or nothing, which is
 * VIAK as a whole (Marcel, 2026-09-24). Not where it is shown: that is
 * `testimonial_placements`, written by the pages' pickers. The subject is what
 * a picker shows beside each quote, and what it sorts by, so the right ones
 * are found on the page they belong to ([[Testimonial::subject]]).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('testimonials', function (Blueprint $table) {
			$table->nullableMorphs('subject');
		});
	}

	public function down(): void
	{
		Schema::table('testimonials', function (Blueprint $table) {
			$table->dropMorphs('subject');
		});
	}
};
