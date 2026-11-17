<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * **An Angebot's logo** (Oliver 2026-11-17, [[14-angebote]] *Logos*): the
 * software maker's mark, shown in every list and overview. A file name on the
 * public disk under `logos/`, served as it is: not a [[Media]] row, because
 * the image pipeline re-encodes and crops, and a logo keeps its transparent
 * ground and its exact shape. Null: no logo, and nothing stands in for one
 * (Marcel, same day). The search's rows carry it too.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('software', function (Blueprint $table) {
			$table->string('logo')->nullable()->after('featured');
		});

		Schema::table('search_entries', function (Blueprint $table) {
			$table->string('logo')->nullable()->after('url');
		});
	}

	public function down(): void
	{
		Schema::table('software', fn (Blueprint $table) => $table->dropColumn('logo'));
		Schema::table('search_entries', fn (Blueprint $table) => $table->dropColumn('logo'));
	}
};
