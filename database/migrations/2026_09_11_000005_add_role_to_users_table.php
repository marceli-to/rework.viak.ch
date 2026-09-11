<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles are a pivot, not a column — they are capabilities a person can hold
 * several of at once, not a rank. See [[Role]] for why the single-column
 * version was wrong.
 *
 * The legacy `roles` lookup table is dropped: three fixed values belong in an
 * enum, not in a table someone can edit.
 */
return new class () extends Migration {
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->uuid()->nullable()->unique()->after('id');
		});

		Schema::create('role_user', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->string('role', 20);
			$table->unique(['user_id', 'role']);
			$table->index('role');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('role_user');

		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('uuid');
		});
	}
};
