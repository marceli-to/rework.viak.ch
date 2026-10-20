<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * *Das könnte dich auch interessieren* (client, 2026-10-20): from a software
 * or a course, the courses and software VIAK recommends with it, in order.
 * Customers put their own bundle together from these, in place of the shop's
 * fixed bundles ([[Recommendations]]).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('recommendations', function (Blueprint $table): void {
			$table->id();
			$table->morphs('owner');
			$table->morphs('item');
			$table->unsignedSmallInteger('order')->default(0);

			$table->unique(['owner_type', 'owner_id', 'item_type', 'item_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('recommendations');
	}
};
