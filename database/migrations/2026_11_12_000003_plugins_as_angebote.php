<?php

declare(strict_types=1);

use App\Models\Software;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * **A plugin is an Angebot of its own** (#72, the client 2026-11-12,
 * [[14-angebote]] *As built: step 4*), replacing #67's plugin products
 * under Rhinoceros. *Plugin* moves from the licence product to the Angebot,
 * which gets the programs it runs in (`software_host`, the dashboard's
 * *Läuft in*): optional, as V-Ray or Corona run in many.
 *
 * On a database that has the catalogue: the six Rhino plugins and Rhino.io
 * (under CINEMA 4D) move into Angebote of their own, created where missing
 * (Drakon has one, with its courses), each with its product's categories;
 * V-Ray, Corona Renderer, Enscape and Veras are ticked *Plugin*, with no
 * host. `database/data/licences.json` carries the same for a fresh import.
 *
 * Eloquent for the new Angebote: [[Software]] makes the slug on create.
 */
return new class extends Migration
{
	/** Product title => [Angebot, the programs it runs in]. */
	private const MOVES = [
		'Bongo 2' => ['Bongo', ['Rhinoceros']],
		'VisualARQ 3' => ['VisualARQ', ['Rhinoceros']],
		'Lands Design 6' => ['Lands Design', ['Rhinoceros']],
		'Karamba3D PRO' => ['Karamba3D', ['Rhinoceros']],
		'MatrixGold' => ['MatrixGold', ['Rhinoceros']],
		'Drakon' => ['Drakon', ['Rhinoceros']],
		'Rhino.io' => ['Rhino.io', ['CINEMA 4D']],
	];

	private const UNIVERSAL = ['V-Ray', 'Corona Renderer', 'Enscape', 'Veras'];

	public function up(): void
	{
		Schema::table('software', function (Blueprint $table) {
			$table->boolean('plugin')->default(false)->after('featured');
		});

		Schema::create('software_host', function (Blueprint $table) {
			$table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
			$table->foreignId('host_id')->constrained('software')->cascadeOnDelete();
			$table->primary(['software_id', 'host_id']);
		});

		$id = fn (string $title) => DB::table('software')->where('title->de', $title)->whereNull('deleted_at')->value('id');

		foreach (self::MOVES as $product => [$angebot, $hosts]) {
			$row = DB::table('licence_products')->where('title->de', $product)->whereNull('deleted_at')->first();

			if ($row === null) {
				continue;
			}

			$software = $id($angebot) ?? Software::create([
				'title' => ['de' => $angebot],
				'order' => (int) DB::table('software')->where('id', $row->software_id)->value('order'),
				'publish' => true,
			])->id;

			DB::table('licence_products')->where('id', $row->id)->update(['software_id' => $software]);
			DB::table('software')->where('id', $software)->update(['plugin' => true]);

			foreach ($hosts as $host) {
				if ($host = $id($host)) {
					DB::table('software_host')->insertOrIgnore(['software_id' => $software, 'host_id' => $host]);
				}
			}

			if (! DB::table('software_category')->where('software_id', $software)->exists()) {
				DB::table('software_category')->insert(
					DB::table('licence_product_category')->where('licence_product_id', $row->id)->pluck('category_id')
						->map(fn (int $category) => ['software_id' => $software, 'category_id' => $category])->all(),
				);
			}
		}

		DB::table('software')->whereIn('title->de', self::UNIVERSAL)->update(['plugin' => true]);

		Schema::table('licence_products', fn (Blueprint $table) => $table->dropColumn('plugin'));
	}

	public function down(): void
	{
		Schema::table('licence_products', function (Blueprint $table) {
			$table->boolean('plugin')->default(false)->after('manufacturer_id');
		});
		Schema::dropIfExists('software_host');
		Schema::table('software', fn (Blueprint $table) => $table->dropColumn('plugin'));
	}
};
