<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers which participant band an event was last known to be in
 * ([[ParticipantThreshold]]).
 *
 * The guard that lets the rework compare with `>=` and `<=` instead of `==`. A
 * notification fires when the *band* changes, so a count that jumps over a
 * threshold still notifies, and a listener that runs twice on the same booking
 * does nothing the second time.
 *
 * Null means "never evaluated", which is what every ported event is: legacy kept
 * no such record, and back-filling one would send 360 courses' worth of
 * notifications about thresholds crossed years ago.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('events', function (Blueprint $table) {
			$table->string('participant_threshold', 20)->nullable()->after('max_participants');
		});
	}

	public function down(): void
	{
		Schema::table('events', function (Blueprint $table) {
			$table->dropColumn('participant_threshold');
		});
	}
};
