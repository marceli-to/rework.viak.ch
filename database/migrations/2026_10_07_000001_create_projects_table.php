<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Vorhaben ([[04-content]]): *Räume visualisieren*, *Objekte entwerfen* …,
 * the tiles under *Was möchtest du machen?* on the homepage and a page each.
 *
 * The 2026-09-23 review cut the mockup's page down to **a title, a text and
 * the offer list** (Räume markers 1–3): no tools box, no *Andere Vorhaben*. So
 * a row is its copy, and the offer list is the courses picked for it, in the
 * page's own order. Licences join that list when they exist (chunk 05).
 *
 * `teaser` is the tile's second line on the homepage (*Enscape, Twinmotion,
 * Lumion, V-Ray*); `lead` the page's bold opening line.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('projects', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->json('title');
			$table->json('slug');
			$table->json('teaser')->nullable();
			$table->json('lead')->nullable();
			$table->json('text')->nullable();
			$table->boolean('publish')->default(false);
			$table->unsignedSmallInteger('order')->default(0);
			$table->timestamps();

			$table->index(['publish', 'order']);
		});

		Schema::create('course_project', function (Blueprint $table) {
			$table->id();
			$table->foreignId('project_id')->constrained()->cascadeOnDelete();
			$table->foreignId('course_id')->constrained()->cascadeOnDelete();
			$table->unsignedSmallInteger('order')->default(0);

			$table->unique(['project_id', 'course_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('course_project');
		Schema::dropIfExists('projects');
	}
};
