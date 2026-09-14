<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference data, not user data: 249 rows that change when the world does.
 *
 * Keyed by ISO 3166-1 alpha-2 rather than an auto-increment id, so a country
 * survives a re-import of the list and an address can be read without a join.
 * `order` exists only to float Switzerland to the top of the dropdown — 545 of
 * 578 users are Swiss.
 */
return new class () extends Migration {
	public function up(): void
	{
		Schema::create('countries', function (Blueprint $table) {
			$table->char('code', 2)->primary();
			$table->json('name');
			$table->unsignedTinyInteger('order')->default(99);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('countries');
	}
};
