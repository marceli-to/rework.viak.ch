<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The PDFs a customer holds ([[08-accounts]]).
 *
 * 1,162 legacy rows over **1,005 distinct files** — 568 invoices and 594
 * participation confirmations. `01-schema.md` deliberately left these to
 * "whichever chunk owns generated documents"; this is that chunk.
 *
 * ## The storage decision, which is a fix rather than a preference
 *
 * Legacy wrote them to `storage/app/public/files/{user_uuid}/` and stored the
 * **public** path in `uri`. `public/storage` is symlinked, so every invoice and
 * every participation confirmation is fetchable **without authenticating** —
 * protected by nothing but the uuid in the path being unguessable.
 *
 * Here the files live outside the public root and are served by a route with a
 * policy. So there is no `uri` column: the path is derived from the owner and
 * the filename, and a row cannot accidentally describe a public URL.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('user_documents', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();

			$table->foreignId('user_id')->constrained()->restrictOnDelete();

			$table->string('type', 30);
			$table->string('filename');

			// What the document is *about* — an invoice or a booking. Legacy
			// called this `fileable` and used it the same way.
			$table->nullableMorphs('documentable');

			// The document's own date, which is not `created_at`. Legacy set
			// this from `events.closed_at` for a participation confirmation, and
			// the 157 duplicate rows share a file but not a timestamp — so the
			// created date is the one thing that cannot be trusted to identify
			// a document.
			$table->date('date')->nullable();

			$table->timestamps();

			// One document per file per user. The constraint legacy did not have:
			// 17 of its paths carry more than one row, up to **37 rows for a
			// single booking**, because `EventClosedStudent` generated the PDF
			// and inserted the row from inside a Mailable that the queue could
			// re-run.
			$table->unique(['user_id', 'filename']);
			$table->index(['user_id', 'type']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('user_documents');
	}
};
