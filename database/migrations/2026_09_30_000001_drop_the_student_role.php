<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Student role goes: every account is a customer (`12-customers.md`).
 *
 * Only a local database holds these rows. Production is ported afresh at
 * cutover, and the port no longer writes the role ([[PortUsers]]). There is
 * nothing to put back on the way down: a customer needed no row.
 */
return new class extends Migration
{
	public function up(): void
	{
		DB::table('role_user')->where('role', 'student')->delete();
	}

	public function down(): void
	{
		// Deliberately empty; see above.
	}
};
