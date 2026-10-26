<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The site search's index ([[15-search]], [[SearchIndex]]): one row per
 * searchable record and locale, as plain text. The records' own columns are
 * translatable JSON, which a FULLTEXT index would read keys and markup out
 * of; this is the text a visitor would read, and nothing else.
 *
 * Two FULLTEXT indexes because MySQL matches against an index's exact
 * columns: `title` alone, to weigh a hit in the title above one in the body,
 * and `title, body` for the match itself.
 *
 * `url` and `lead` are stored so a result list needs no second query; both
 * change only when the record is saved, which rewrites the row.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('search_entries', function (Blueprint $table) {
			$table->id();
			$table->morphs('searchable');
			$table->string('locale', 5);
			$table->string('type', 20);
			$table->string('title');
			$table->text('body');
			$table->string('lead', 300)->nullable();
			$table->string('url');
			$table->timestamps();

			$table->unique(['searchable_type', 'searchable_id', 'locale']);
			$table->fullText('title');
			$table->fullText(['title', 'body']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('search_entries');
	}
};
