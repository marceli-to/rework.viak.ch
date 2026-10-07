<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Vorhaben's *Metatags + SEO*, as a course has them (Marcel, 2026-10-07):
 * the page's meta description and keywords, translatable as `courses`'
 * `seo_description` and `seo_tags` are ([[04-content]]).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('projects', function (Blueprint $table) {
			$table->json('seo_description')->nullable()->after('text');
			$table->json('seo_tags')->nullable()->after('seo_description');
		});
	}

	public function down(): void
	{
		Schema::table('projects', function (Blueprint $table) {
			$table->dropColumn(['seo_description', 'seo_tags']);
		});
	}
};
