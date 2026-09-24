<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a testimonial appears, and in what order there (Marcel, 2026-09-24).
 *
 * A page picks its testimonials — the course form, the software pages and the
 * homepage form will each have a picker — so the link lives here rather than
 * as a foreign key on the testimonial: **one quote can stand on several
 * pages**, the Rhino course, Rhinoceros and the homepage, and each page keeps
 * its own order. A new kind of page is a new `placeable_type`, not a schema
 * change ([[HasTestimonials]]).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('testimonial_placements', function (Blueprint $table) {
			$table->id();
			$table->foreignId('testimonial_id')->constrained()->cascadeOnDelete();
			$table->morphs('placeable');
			$table->unsignedSmallInteger('order')->default(0);
			$table->timestamps();

			$table->unique(['testimonial_id', 'placeable_type', 'placeable_id'], 'testimonial_placements_unique');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('testimonial_placements');
	}
};
