<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Testimonials — quotes VIAK enters, replacing the Elfsight Google reviews
 * ([[04-content]], `Open-Questions.md` #18, decided in the 2026-09-23 review).
 *
 * What the mockups show of one: the quote, a name, and one line of context —
 * *Architekturbüro, Zürich*. No photo. Where they appear (the homepage,
 * Firmenschulung, the software pages) is chosen by those pages, not stored
 * here — the `featured` column this created is dropped by the next migration.
 *
 * The quote and the context are translatable like every other content text,
 * although the admin writes German only (`04-content.md`).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('testimonials', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->json('quote');
			$table->string('name');
			$table->json('context')->nullable();
			$table->boolean('featured')->default(false);
			$table->boolean('publish')->default(false);
			$table->unsignedSmallInteger('order')->default(0);
			$table->timestamps();

			$table->index(['publish', 'order']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('testimonials');
	}
};
