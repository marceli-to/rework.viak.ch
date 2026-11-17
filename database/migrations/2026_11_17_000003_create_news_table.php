<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * **News on the homepage** (Oliver in Roxy, 2026-11-12, built 2026-11-17):
 * beside *Nächste Kurstermine*, a small picture, a short text and an arrow to
 * a page VIAK picks (*Rhino 9 jetzt verfügbar* → the Rhino page). Legacy's
 * `news` had the same four things (title, text, link, picture); its five rows
 * are not ported, VIAK writes new ones.
 *
 * `link` is a key, not an address: `software:{uuid}`, `course:{uuid}` or
 * `page:{name}`, resolved when the page is drawn ([[NewsLink]]), so a renamed
 * Angebot does not break it and one off the site loses its arrow.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('news', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->json('title');
			$table->json('text')->nullable();
			$table->string('link')->nullable();
			$table->boolean('publish')->default(false);
			$table->unsignedSmallInteger('order')->default(0);
			$table->timestamps();

			$table->index(['publish', 'order']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('news');
	}
};
