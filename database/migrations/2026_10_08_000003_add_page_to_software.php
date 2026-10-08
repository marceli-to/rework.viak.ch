<?php

declare(strict_types=1);

use App\Support\Slug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A software gets a page of its own, drawn as a course page is (Marcel,
 * 2026-10-08, [[05-licences]]): so the columns are the course's, by the same
 * names, translatable as theirs are. *Weitere Informationen* is one column,
 * `information`, where a course has two (Marcel, 2026-10-08: the second
 * editor had no label and no use).
 *
 * Its categories are the courses' categories, so the software list filters
 * by the same links the course list does: `software_category`.
 *
 * Every row there is gets its slug here, from its German title, numbered
 * where two would meet.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('software', function (Blueprint $table) {
			$table->json('slug')->nullable()->after('uuid');
			$table->json('subtitle')->nullable()->after('title');
			$table->json('short_description')->nullable()->after('subtitle');
			$table->json('full_description')->nullable()->after('short_description');
			$table->json('information')->nullable()->after('full_description');
			$table->json('seo_description')->nullable()->after('information');
			$table->json('seo_tags')->nullable()->after('seo_description');
		});

		Schema::create('software_category', function (Blueprint $table) {
			$table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
			$table->foreignId('category_id')->constrained()->cascadeOnDelete();
			$table->primary(['software_id', 'category_id']);
		});

		$taken = [];
		foreach (DB::table('software')->orderBy('id')->get(['id', 'title']) as $row) {
			$title = json_decode((string) $row->title, true)['de'] ?? '';
			$base = Slug::forTitles($title !== '' ? $title : 'software')['de'];
			$slug = $base;
			for ($n = 2; in_array($slug, $taken, true); $n++) {
				$slug = "{$base}-{$n}";
			}
			$taken[] = $slug;
			DB::table('software')->where('id', $row->id)->update(['slug' => json_encode(['de' => $slug])]);
		}
	}

	public function down(): void
	{
		Schema::dropIfExists('software_category');

		Schema::table('software', function (Blueprint $table) {
			$table->dropColumn(['slug', 'subtitle', 'short_description', 'full_description', 'information', 'seo_description', 'seo_tags']);
		});
	}
};
