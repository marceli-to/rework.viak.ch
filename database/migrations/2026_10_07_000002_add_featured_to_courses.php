<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * *Beliebte Angebote* on the homepage (the review's markers 5 and 6,
 * [[04-content]]): a course is flagged in its form and the homepage lists
 * the flagged ones in the catalogue's order. Software joins the list when it
 * is more than a filter term (chunk 05).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('courses', function (Blueprint $table) {
			$table->boolean('featured')->default(false)->after('publish');
		});
	}

	public function down(): void
	{
		Schema::table('courses', function (Blueprint $table) {
			$table->dropColumn('featured');
		});
	}
};
