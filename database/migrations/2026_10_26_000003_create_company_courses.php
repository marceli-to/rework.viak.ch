<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firmenkurse ([[16-firmenkurse]]): a course held for one company, and the
 * one invoice it raises.
 *
 * - `companies`: the company as an invoice address, no account. Its id is the
 *   customer number in Run My Accounts, so it is kept and picked again.
 * - `company_courses`: what was taught (a catalogue course or a free title),
 *   where, for how many, and where the invoice is mailed. Its own table, not
 *   an `events` row: nothing public, no expert, no reminder may see it.
 * - `company_course_dates`, `company_course_lines`: the days, and the lines
 *   the invoice is issued from.
 * - `invoices`: `user_id` nullable and `company_id` beside it, one of the two
 *   set. Every invoice before this one has a user.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('companies', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->string('company');
			$table->string('first_name')->nullable();
			$table->string('last_name')->nullable();
			$table->string('street');
			$table->string('street_no', 15)->nullable();
			$table->string('zip', 15);
			$table->string('city');
			$table->string('country_code', 2)->default('ch');
			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create('company_courses', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->foreignId('company_id')->constrained()->restrictOnDelete();
			$table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
			$table->string('title')->nullable();
			$table->string('location')->nullable();
			$table->unsignedSmallInteger('participants')->nullable();
			$table->string('email');
			$table->text('remarks')->nullable();
			$table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create('company_course_dates', function (Blueprint $table) {
			$table->id();
			$table->foreignId('company_course_id')->constrained()->cascadeOnDelete();
			$table->date('date');
			$table->time('time_start')->nullable();
			$table->time('time_end')->nullable();
			$table->timestamps();
		});

		Schema::create('company_course_lines', function (Blueprint $table) {
			$table->id();
			$table->foreignId('company_course_id')->constrained()->cascadeOnDelete();
			$table->unsignedSmallInteger('position');
			$table->string('description');
			$table->decimal('net', 10, 2);
			$table->boolean('taxed')->default(false);
			$table->timestamps();
		});

		Schema::table('invoices', function (Blueprint $table) {
			$table->foreignId('user_id')->nullable()->change();
			$table->foreignId('company_id')->nullable()->after('user_id')->constrained()->restrictOnDelete();
		});
	}

	public function down(): void
	{
		Schema::table('invoices', function (Blueprint $table) {
			$table->dropConstrainedForeignId('company_id');
		});

		Schema::dropIfExists('company_course_lines');
		Schema::dropIfExists('company_course_dates');
		Schema::dropIfExists('company_courses');
		Schema::dropIfExists('companies');
	}
};
