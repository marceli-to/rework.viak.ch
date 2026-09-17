<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		// Legacy carries `fix` and `percent` as two booleans that are never
		// both set — 77 fixed, 30 percentage, 0 ambiguous. One enum column
		// makes the invalid third state unrepresentable.
		Schema::create('discount_codes', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();

			$table->string('code', 14)->unique();
			$table->string('type', 10);
			$table->decimal('amount', 8, 2);

			$table->date('valid_from')->nullable();
			$table->date('valid_to')->nullable();
			$table->string('remarks')->nullable();

			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create('bookings', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->string('number', 6)->unique();

			$table->foreignId('event_id')->constrained()->restrictOnDelete();
			$table->foreignId('user_id')->constrained()->restrictOnDelete();

			// What this booking was sold for, captured at booking time. The
			// event's fee can change afterwards and this must not follow it.
			//
			// It is not what was finally *charged*: six bookings were invoiced
			// at exactly half their course_fee under an arrangement recorded
			// only on the invoice. The invoice is the money, this is the offer.
			$table->decimal('course_fee', 8, 2)->default(0);

			$table->foreignId('discount_code_id')->nullable()->constrained()->nullOnDelete();
			$table->decimal('discount_amount', 8, 2)->default(0);

			// CHF 80 laptop for students without a suitable machine. Billed on
			// its own invoice — see `03-invoices.md`.
			$table->boolean('has_rental')->default(false);

			// Where the bill goes, frozen at booking time. Legacy stored a
			// rendered HTML fragment ("Name<br>Street<br>Zip City"), which
			// cannot be re-rendered, corrected, or exported. Structured here,
			// null meaning "use the user's own address" as it does today.
			$table->json('invoice_address')->nullable();

			$table->timestamp('booked_at');
			$table->timestamp('cancelled_at')->nullable();

			$table->timestamps();
			$table->softDeletes();

			$table->index(['event_id', 'cancelled_at']);
			$table->index(['user_id', 'booked_at']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('bookings');
		Schema::dropIfExists('discount_codes');
	}
};
