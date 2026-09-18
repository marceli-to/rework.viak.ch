<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notes an expert or an admin posts to a course ([[08-accounts]]).
 *
 * Built properly rather than given the bookmark treatment, because the data says
 * so: **251 messages across 122 events**, and the rate is steady rather than
 * trailing off — 51 in 2023, 98 in 2024, 64 in 2025, 38 by September 2026.
 *
 * It is lopsided in a way worth knowing before designing the screen: one user
 * wrote 129 of the 251 and an admin wrote 60, with seven experts sharing the
 * remaining 39. This is an admin tool with an expert-facing corner, not a
 * per-course conversation.
 *
 * Legacy's `messageable` morph only ever held an Event, across all 251 rows, so
 * the column is not carried over — a polymorphic relation with one possible
 * value is a guess about the future priced as complexity today.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('messages', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();

			$table->foreignId('event_id')->constrained()->cascadeOnDelete();

			// Who wrote it. Restricted rather than cascading: a message that has
			// been mailed to twelve students is not undone by deleting an
			// account.
			$table->foreignId('user_id')->constrained()->restrictOnDelete();

			$table->string('subject');
			$table->text('body');

			$table->timestamps();
			$table->softDeletes();

			$table->index(['event_id', 'created_at']);
		});

		// Who it went to, as it stood when it was sent. Legacy kept the same
		// record and it is worth keeping: the recipients of a message are the
		// people booked *at that moment*, and a later cancellation must not
		// rewrite who was told what.
		Schema::create('message_user', function (Blueprint $table) {
			$table->foreignId('message_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->timestamp('created_at')->nullable();

			$table->primary(['message_id', 'user_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('message_user');
		Schema::dropIfExists('messages');
	}
};
