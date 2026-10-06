<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pages that are not a course or a software but still pick things —
 * Firmenschulung's testimonials first, the homepage's later ([[Page]]). One
 * row per page, by a fixed `key`; the copy stays in Blade until a page needs
 * it editable.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('pages', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->string('key')->unique();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('pages');
	}
};
