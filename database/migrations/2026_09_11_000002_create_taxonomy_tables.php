<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The five course taxonomies. Identical shape, so one migration rather than
 * five near-copies — they differ only in what they mean, not in structure.
 *
 * `software` earns its keep twice over in the rework: it already tags which
 * tool a course teaches, and it is the natural anchor for the licence
 * catalogue in [[05-licences]].
 */
return new class () extends Migration {
	private const TABLES = ['categories', 'levels', 'languages', 'software', 'tags'];

	public function up(): void
	{
		foreach (self::TABLES as $name) {
			Schema::create($name, function (Blueprint $table) {
				$table->id();
				$table->uuid()->unique();
				$table->json('title');
				$table->unsignedSmallInteger('order')->default(0);
				$table->boolean('publish')->default(true);
				$table->timestamps();
				$table->softDeletes();
			});
		}
	}

	public function down(): void
	{
		foreach (array_reverse(self::TABLES) as $name) {
			Schema::dropIfExists($name);
		}
	}
};
