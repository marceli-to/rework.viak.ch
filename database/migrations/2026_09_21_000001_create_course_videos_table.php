<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy `course_videos`, which chunk 01 never carried across ([[01-schema]]).
 *
 * Found on 2026-09-21 while rebuilding the course page: the live page has a
 * **Videos** collapsible between the dates and the facts, fed by this table,
 * and 19 of 32 courses have a row in it. No `.rework` document mentions the
 * table, so it was missed rather than dropped.
 *
 * One row per course today, but the legacy schema allows many and the page
 * loops, so this does too.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('course_videos', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->foreignId('course_id')->constrained()->cascadeOnDelete();

			// Translatable like every other piece of course copy, although
			// legacy holds it as a plain `varchar`. `PortCourses::translated()`
			// wraps the string as `{de: …}` on the way in.
			$table->json('title')->nullable();

			// An `<iframe>` the editor pasted. Rendered unescaped, which is why
			// `CourseVideo` is the only model the site trusts with raw HTML
			// beyond the rich-text fields.
			$table->text('code');

			// Legacy's default is -1, which sorts an unordered row first. Kept
			// at 0 here: the list is ordered by `order` then `id`, so a new row
			// lands at the end rather than at the top.
			$table->integer('order')->default(0);
			$table->boolean('publish')->default(true);

			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('course_videos');
	}
};
