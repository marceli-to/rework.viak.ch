<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two fields the Angebot pages need, whatever the client answers about their
 * layout ([[14-angebote]]):
 *
 * - `licence_products.features`: the ticked lines on a licence row
 *   (*Kauflizenz, unbefristet*, *Windows und Mac*), one per line;
 * - `software.licence_note`: what stands where the licence price would, for
 *   a software VIAK does not sell itself (*Lizenz direkt bei Adobe*).
 *
 * Both translatable, as the columns beside them are.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('licence_products', function (Blueprint $table) {
			$table->json('features')->nullable()->after('description');
		});

		Schema::table('software', function (Blueprint $table) {
			$table->json('licence_note')->nullable()->after('information');
		});
	}

	public function down(): void
	{
		Schema::table('licence_products', fn (Blueprint $table) => $table->dropColumn('features'));
		Schema::table('software', fn (Blueprint $table) => $table->dropColumn('licence_note'));
	}
};
