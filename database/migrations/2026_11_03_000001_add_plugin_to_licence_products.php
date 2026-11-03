<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A licence product is a **Software** licence or a **Plugin** ([[14-angebote]],
 * #67, the client 2026-11-03): the plugins stay under the Angebot they run
 * in (Bongo under Rhinoceros) and are told apart by this. The Angebot page
 * lists them under *Plugins*, the Software list's tile says which it is.
 *
 * The products `database/data/licences.json` marks (the six under
 * Rhinoceros) are set here too, so a database that already holds the
 * catalogue gets them with `migrate`. VIAK sets the rest in the dashboard.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('licence_products', function (Blueprint $table) {
			$table->boolean('plugin')->default(false)->after('manufacturer_id');
		});

		$path = base_path('database/data/licences.json');

		if (! is_file($path)) {
			return;
		}

		foreach (json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) as $group) {
			$titles = collect($group['products'])->where('plugin', true)->pluck('title')->all();

			if ($titles === []) {
				continue;
			}

			$software = DB::table('software')->where('title->de', $group['software'])->value('id');

			DB::table('licence_products')
				->where('software_id', $software)
				->whereIn('title->de', $titles)
				->update(['plugin' => true]);
		}
	}

	public function down(): void
	{
		Schema::table('licence_products', function (Blueprint $table) {
			$table->dropColumn('plugin');
		});
	}
};
