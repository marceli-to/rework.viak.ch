<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alternate invoice addresses — 120 of them, typically an employer paying for
 * a staff member's course. A user's own address lives on `users`; this table
 * is only the "send the bill somewhere else" case.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('user_addresses', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();

			$table->string('first_name')->nullable();
			$table->string('last_name')->nullable();
			$table->string('company')->nullable();
			$table->string('street')->nullable();
			$table->string('street_no', 15)->nullable();
			$table->string('zip', 15)->nullable();
			$table->string('city')->nullable();
			$table->char('country_code', 2)->nullable();

			$table->timestamps();
			$table->softDeletes();

			$table->foreign('country_code')->references('code')->on('countries')->nullOnDelete();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('user_addresses');
	}
};
