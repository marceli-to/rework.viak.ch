<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The money ([[03-invoices]]).
 *
 * An invoice is a **header with line items**, and it covers what became
 * billable at the same moment. That rule reproduces legacy's per-course split
 * exactly — two courses confirming three weeks apart are still two invoices,
 * because invoices are raised on confirmation and not at checkout — and stops
 * splitting in the one place where the split only ever existed to work around
 * the schema: a course and its laptop rental are now one document instead of
 * two numbers and two QR bills.
 *
 * `invoices.booking_id` is gone. The link to what was sold lives on the line,
 * which is what lets a licence (no booking at all) and a course share a table.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('invoices', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();

			// Six digits, one continuous sequence since 2023 with no yearly
			// reset — 569 issued so far. Never reused, so soft-deleted rows
			// keep their number ([[InvoiceNumber]]).
			$table->string('number', 6)->unique();

			$table->foreignId('user_id')->constrained()->restrictOnDelete();

			$table->string('status', 10)->default(InvoiceStatus::Open->value);

			// The document's own date. Legacy set it to the day of creation on
			// all 569 rows and printed it as "Zürich, 15.03.2023".
			$table->date('date');

			// A DATE, and explicitly nullable, for a reason worth spelling out:
			// legacy declared this a bare `timestamp`, it was the first
			// TIMESTAMP column in the table, and MySQL silently attached
			// `ON UPDATE CURRENT_TIMESTAMP`. Every write to an invoice reset
			// its payment deadline to now — all 541 paid invoices lost their
			// original deadline, and open ones were bumped to today, daily, so
			// the deadline could never pass. A DATE column cannot acquire that
			// rule. See `Todo.md` for what the port owes the cutover database.
			$table->date('due_at')->nullable();

			$table->timestamp('paid_at')->nullable();
			$table->timestamp('cancelled_at')->nullable();

			// Legacy wrote a sentence into `cancel_reason` and then had to be
			// grepped to find out why anything was cancelled. Two sentences
			// ever occurred, so this is an enum plus, for a replacement, the
			// invoice that replaced it ([[CancellationReason]]).
			$table->string('cancellation_reason', 20)->nullable();
			$table->foreignId('replaced_by_invoice_id')->nullable()
				->constrained('invoices')->nullOnDelete();

			// Where the bill went, frozen at issue. Structured like the
			// booking's; the 131 historical ones carry across as the `lines`
			// that were actually printed. Null means the user's own address.
			$table->json('invoice_address')->nullable();

			// Sums of the lines, stored rather than computed on read. An
			// invoice is a document that was sent, and it has to keep saying
			// what it said. `discount` is a positive amount that was taken off.
			$table->decimal('net', 8, 2)->default(0);
			$table->decimal('discount', 8, 2)->default(0);
			$table->decimal('vat', 8, 2)->default(0);
			$table->decimal('grand_total', 8, 2)->default(0);

			// The PDF as legacy named it. `user_documents` (568 invoice PDFs)
			// belongs to whichever chunk owns generated documents, and the
			// rendering is not built yet — but the filename is the only link
			// back to the file a customer already has, so it comes across.
			$table->string('filename')->nullable();

			$table->timestamps();
			$table->softDeletes();

			$table->index(['status', 'due_at']);
			$table->index(['user_id', 'date']);
		});

		Schema::create('invoice_items', function (Blueprint $table) {
			$table->id();
			$table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

			// COURSE | RENTAL | LICENCE. Carries the VAT rule with it: a
			// course is exempt, the other two are not ([[InvoiceItemType]]).
			$table->string('type', 10);

			// Booking, or a licence order item once chunk 05 exists. Nullable
			// because a line has to survive its subject: what was billed is
			// settled by `description`, not by a live join.
			$table->nullableMorphs('itemable');

			// Frozen at issue, never derived at render time. A course renamed
			// in 2027 must not retitle an invoice issued in 2024.
			$table->string('description');

			// What legacy printed in the "Kurs-Nummer" column.
			$table->string('reference')->nullable();

			$table->unsignedSmallInteger('position')->default(1);

			// `vat = round((net - discount) * vat_rate / 100, 2)` and
			// `total = net - discount + vat`, both to the centime ([[Vat]]).
			$table->decimal('net', 8, 2)->default(0);
			$table->decimal('discount', 8, 2)->default(0);
			$table->decimal('vat_rate', 5, 2)->default(0);
			$table->decimal('vat', 8, 2)->default(0);
			$table->decimal('total', 8, 2)->default(0);

			$table->timestamps();

			$table->index(['invoice_id', 'position']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('invoice_items');
		Schema::dropIfExists('invoices');
	}
};
