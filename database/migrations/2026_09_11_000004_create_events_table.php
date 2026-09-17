<?php

declare(strict_types=1);

use App\Enums\EventState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('events', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();

			$table->date('date');
			$table->date('registration_until')->nullable();

			$table->unsignedSmallInteger('min_participants')->default(1);
			$table->unsignedSmallInteger('max_participants')->default(1);

			// Single source of truth. Legacy split this across nullable
			// timestamps and spatie flag rows, which drifted apart.
			$table->string('state', 20)->default(EventState::Planned->value)->index();
			$table->timestamp('confirmed_at')->nullable();
			$table->timestamp('cancelled_at')->nullable();
			$table->timestamp('closed_at')->nullable();

			$table->boolean('rentals_available')->default(false);
			$table->boolean('online')->default(false);
			$table->boolean('free_of_charge')->default(false);
			$table->boolean('publish')->default(true);

			// Overrides courses.fee when set.
			$table->decimal('fee', 8, 2)->nullable();

			$table->foreignId('course_id')->constrained()->cascadeOnDelete();
			$table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();

			$table->timestamps();
			$table->softDeletes();

			$table->index(['date', 'publish']);
		});

		Schema::create('event_dates', function (Blueprint $table) {
			$table->id();
			$table->foreignId('event_id')->constrained()->cascadeOnDelete();
			$table->date('date');
			$table->time('time_start')->nullable();
			$table->time('time_end')->nullable();
			$table->timestamps();
			$table->index(['event_id', 'date']);
		});

		Schema::create('event_expert', function (Blueprint $table) {
			$table->id();
			$table->foreignId('event_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->unique(['event_id', 'user_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('event_expert');
		Schema::dropIfExists('event_dates');
		Schema::dropIfExists('events');
	}
};
