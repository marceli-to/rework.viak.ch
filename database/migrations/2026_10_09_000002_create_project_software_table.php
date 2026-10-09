<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Vorhaben's offer list takes software too ([[04-content]], Marcel
 * 2026-10-09): picked and ordered in its form as the courses are, and drawn
 * after them on the page, as *Beliebte Angebote* draws the two.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('project_software', function (Blueprint $table) {
			$table->id();
			$table->foreignId('project_id')->constrained()->cascadeOnDelete();
			$table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
			$table->unsignedSmallInteger('order')->default(0);

			$table->unique(['project_id', 'software_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('project_software');
	}
};
