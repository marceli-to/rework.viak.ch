<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The team on the *Über uns* page ([[04-content]], the 2026-09-23 review's Team
 * marker 3).
 *
 * Legacy has a `team_members` table and it is **empty**, so nothing is ported
 * (`09-public-site.md`, *The Kontakt page*). Its shape is the mockup's: a
 * portrait, a name, and one line of what the person does. Legacy split the name
 * and kept a translatable `info` text that no screen ever showed; the mockup
 * has neither, so neither is here.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('team_members', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->string('name');
			$table->json('role')->nullable();
			$table->boolean('publish')->default(false);
			$table->unsignedSmallInteger('order')->default(0);
			$table->timestamps();

			$table->index(['publish', 'order']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('team_members');
	}
};
