<?php

declare(strict_types=1);

use App\Models\Software;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * A plugin's hosts are **picked from the Software list** ([[05-licences]],
 * Marcel 2026-10-09), where they were names typed with commas: *Rhino* there
 * and *Rhinoceros* in the list were two spellings of one program, and nothing
 * stopped a third.
 *
 * The names move onto Software rows: matched case-blind, through the three
 * short names the import used, and created (unpublished, so no filter lists
 * them) where the list had none, which was Archicad and Maya. An order line
 * keeps the host's name as text, frozen as everything on it is.
 */
return new class extends Migration
{
	/** The import's short names for programs the list spells out. */
	private const ALIASES = ['rhino' => 'rhinoceros', 'sketchup' => 'sketchup pro'];

	public function up(): void
	{
		Schema::create('licence_product_host', function (Blueprint $table) {
			$table->id();
			$table->foreignId('licence_product_id')->constrained()->cascadeOnDelete();
			$table->foreignId('software_id')->constrained('software')->cascadeOnDelete();

			$table->unique(['licence_product_id', 'software_id']);
		});

		$software = Software::withTrashed()->get();

		foreach (DB::table('licence_products')->whereNotNull('hosts')->get(['id', 'hosts']) as $product) {
			foreach (json_decode($product->hosts, true) ?: [] as $name) {
				$key = self::ALIASES[Str::lower(trim($name))] ?? Str::lower(trim($name));
				$host = $software->first(fn (Software $item) => Str::lower((string) $item->getTranslation('title', 'de', false)) === $key);

				if ($host === null) {
					$host = Software::create(['title' => ['de' => trim($name)], 'publish' => false, 'order' => (int) Software::max('order') + 1]);
					$software->push($host);
				}

				DB::table('licence_product_host')->insertOrIgnore(['licence_product_id' => $product->id, 'software_id' => $host->id]);
			}
		}

		Schema::table('licence_products', function (Blueprint $table) {
			$table->dropColumn('hosts');
		});
	}

	public function down(): void
	{
		Schema::table('licence_products', function (Blueprint $table) {
			$table->json('hosts')->nullable()->after('description');
		});

		Schema::dropIfExists('licence_product_host');
	}
};
