<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `events.rentals_available` back to a **count of laptops** ([[06-bookings]]).
 *
 * Legacy's column is the number of machines in the room — 0 on 236 dates, 1 on
 * 17, 2 on 78, 3 on 29 — and a laptop is offered only while fewer than that are
 * rented. Chunk 01 made it a boolean and `PortCourses` wrote `(bool)`, so every
 * room with any laptops became a room with unlimited ones (`Todo.md`, *Rental
 * capacity*, found 2026-09-24).
 *
 * `true` becomes 1 here, which undercounts rather than overcounts until the
 * port runs again and carries the real number.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('events', function (Blueprint $table) {
			$table->unsignedTinyInteger('rentals_available')->default(0)->change();
		});
	}

	public function down(): void
	{
		Schema::table('events', function (Blueprint $table) {
			$table->boolean('rentals_available')->default(false)->change();
		});
	}
};
