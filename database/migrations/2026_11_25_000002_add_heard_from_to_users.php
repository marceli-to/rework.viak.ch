<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * *Wie wurdest du auf uns aufmerksam?* ([[11-tracking]] step 4), on the
 * customer, not on the purchase: it is asked once per customer, on the
 * confirmation page, and a software-only purchase has no `Checkout` to hold it.
 * `heard_from` is a key of `config('tracking.heard_from')`; `heard_from_other`
 * is the free text behind *Andere*; `heard_from_at` is when it was answered.
 * Null throughout: never asked, or not answered.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->string('heard_from')->nullable();
			$table->string('heard_from_other')->nullable();
			$table->timestamp('heard_from_at')->nullable();
		});
	}

	public function down(): void
	{
		Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['heard_from', 'heard_from_other', 'heard_from_at']));
	}
};
