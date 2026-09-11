<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
	public function up(): void
	{
		Schema::create('courses', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();

			// Legacy stored this as tinyint — a ceiling of 127 courses for no reason.
			$table->unsignedInteger('number')->unique();

			$table->json('slug');
			$table->json('title');
			$table->json('subtitle')->nullable();
			$table->json('summary')->nullable();
			$table->json('short_description')->nullable();
			$table->json('full_description')->nullable();

			// Legacy: `additional_information` + `additional_information_1`, the
			// second bolted on in Jan 2023. Kept as two fields, named for what
			// they are rather than for the order they were added.
			$table->json('information_booking')->nullable();
			$table->json('information_content')->nullable();

			// Legacy: facts_column_1..3.
			$table->json('facts')->nullable();

			$table->decimal('fee', 8, 2)->default(0);
			$table->json('reviews')->nullable();
			$table->json('seo_description')->nullable();
			$table->json('seo_tags')->nullable();

			$table->boolean('online')->default(false);
			$table->boolean('publish')->default(false);
			$table->integer('order')->default(0);

			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create('course_taxonomy', function (Blueprint $table) {
			$table->id();
			$table->foreignId('course_id')->constrained()->cascadeOnDelete();
			$table->morphs('taxonomy');
			$table->unique(['course_id', 'taxonomy_type', 'taxonomy_id'], 'course_taxonomy_unique');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('course_taxonomy');
		Schema::dropIfExists('courses');
	}
};
