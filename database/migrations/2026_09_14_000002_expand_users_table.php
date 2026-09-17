<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the Laravel starter `users` table up to what the site actually stores.
 *
 * Three shape changes from legacy, each explained where it happens below:
 * `name` splits into first/last, `gender_id` and `country_id` become the value
 * they pointed at, and the expert profile moves to its own table.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			// Legacy calls these `firstname` and `name` — so `name` means
			// surname on users and full name everywhere else in Laravel.
			// Renaming both ends that ambiguity; User::name() composes them.
			$table->renameColumn('name', 'last_name');
		});

		Schema::table('users', function (Blueprint $table) {
			$table->string('first_name')->default('')->after('uuid');
			$table->string('company')->nullable()->after('last_name');

			// The person's own address. Invoices may be sent somewhere else —
			// see `user_addresses`.
			$table->string('street')->nullable();
			$table->string('street_no', 15)->nullable();
			$table->string('zip', 15)->nullable();
			$table->string('city')->nullable();
			$table->char('country_code', 2)->nullable();
			$table->string('phone', 45)->nullable();

			// Three fixed values in a lookup table legacy nobody can extend
			// meaningfully. An enum says the same thing without the join.
			$table->string('gender', 10)->nullable();

			// Which machine the student brings, so the office knows whether a
			// rental laptop is needed. Legacy stored a multi-select as a CSV
			// string ("macOS,Windows"); a JSON array is the same data typed.
			$table->json('operating_systems')->nullable();

			$table->boolean('subscribe_newsletter')->default(false);

			// 5 of 578 legacy users are soft-deleted. They keep their bookings
			// and invoices, so the rows cannot simply go.
			$table->softDeletes();

			$table->foreign('country_code')->references('code')->on('countries')->nullOnDelete();
			$table->index('last_name');
		});

		// 17 of 578 users have a bio; 10 are on the public page. Four mostly
		// null columns on every row is what a 1:1 table is for, and it keeps
		// the public Experten query off the table holding password hashes.
		Schema::create('expert_profiles', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

			$table->string('title')->nullable();
			$table->text('description')->nullable();

			$table->unsignedSmallInteger('order')->default(999);

			// Both are required to appear publicly, as they are today:
			// `publish` is the admin's draft switch, `visible` the decision to
			// list them. Legacy defaults publish to true for all 578 users,
			// which is why it cannot be the only gate.
			$table->boolean('publish')->default(true);
			$table->boolean('visible')->default(false);

			$table->timestamps();
			$table->index(['visible', 'publish', 'order']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('expert_profiles');

		Schema::table('users', function (Blueprint $table) {
			$table->dropForeign(['country_code']);
			$table->dropIndex(['last_name']);
			$table->dropColumn([
				'first_name', 'company', 'street', 'street_no', 'zip', 'city',
				'country_code', 'phone', 'gender', 'operating_systems',
				'subscribe_newsletter', 'deleted_at',
			]);
			$table->renameColumn('last_name', 'name');
		});
	}
};
