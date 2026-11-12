<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * **The client's software list of 2026-11-12** ([[14-angebote]], *The
 * answers of 2026-11-12*), on a database that already has the catalogue.
 * `database/data/licences.json` carries the same for a fresh import.
 *
 * - A licence gets a **Streichpreis** (`compare_price`): the Rhino 9 Upgrade
 *   is a promo, CHF 360 *statt* 550, as the old shop showed it.
 * - **Rhino 9 replaces Rhino 8.** The product is renamed (and its URL, the
 *   site is not live yet); RHN-1003 and RHN-1013 are added, and Rhino 8's
 *   RHN-1002 and RHN-1012 stay in it, hidden: "Alte Produkte sollen
 *   erhalten bleiben, aber unsichtbar".
 * - **EDU hidden again**, as the list marks it (#75 asks whether that undoes
 *   #70 on purpose): Rhino 9 EDU, EDU Upgrade, VisualARQ Educational.
 * - **RHN-2042 is Drakon's demo**, MatrixGold is RHN-2043 (#74).
 * - **Maxwell Render is deleted** (soft: past orders keep their lines).
 *
 * The data is not put back by `down()`; only the column goes.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('licence_variants', function (Blueprint $table) {
			$table->decimal('compare_price', 10, 2)->nullable()->after('price');
		});

		$this->rhino9();

		DB::table('licence_variants')->whereIn('sku', ['RHN-1091', 'RHN-1092', 'RHN-2008'])->update(['listed' => false]);

		$this->drakonDemo();
		$this->deleteMaxwell();
	}

	public function down(): void
	{
		Schema::table('licence_variants', fn (Blueprint $table) => $table->dropColumn('compare_price'));
	}

	private function rhino9(): void
	{
		$product = DB::table('licence_products')->where('title->de', 'Rhinoceros 8')->first();

		if ($product === null || DB::table('licence_variants')->where('sku', 'RHN-1003')->exists()) {
			return;
		}

		DB::table('licence_products')->where('id', $product->id)->update([
			'title' => json_encode(['de' => 'Rhinoceros 9']),
			'slug' => json_encode(['de' => 'rhinoceros-9']),
			'updated_at' => now(),
		]);

		$this->variant($product->id, 'Vollversion', 'RHN-1003', '930.00', null, 1);
		$this->variant($product->id, 'Upgrade (Promo)', 'RHN-1013', '360.00', '550.00', 2);

		foreach (['RHN-1091' => 3, 'RHN-1092' => 4, 'RHN-1093' => 5, 'RHN-1094' => 6] as $sku => $order) {
			DB::table('licence_variants')->where('sku', $sku)->update(['order' => $order]);
		}

		DB::table('licence_variants')->where('sku', 'RHN-1002')
			->update(['title' => json_encode(['de' => 'Rhino 8 Vollversion']), 'listed' => false, 'order' => 7]);
		DB::table('licence_variants')->where('sku', 'RHN-1012')
			->update(['title' => json_encode(['de' => 'Rhino 8 Update']), 'listed' => false, 'order' => 8]);

		// Our own filler on the Angebot (SoftwareShowcaseSeeder) named Rhino 8.
		foreach (['seo_description', 'seo_tags'] as $column) {
			DB::table('software')->where('title->de', 'Rhinoceros')->update([
				$column => DB::raw("REPLACE(REPLACE({$column}, 'Rhinoceros 8', 'Rhinoceros 9'), 'Rhino 8', 'Rhino 9')"),
			]);
		}
	}

	private function drakonDemo(): void
	{
		DB::table('licence_variants')->where('sku', 'RHN-2042')
			->whereIn('licence_product_id', DB::table('licence_products')->where('title->de', 'MatrixGold')->select('id'))
			->update(['sku' => 'RHN-2043']);

		$drakon = DB::table('licence_products')->where('title->de', 'Drakon')->value('id');

		if ($drakon && ! DB::table('licence_variants')->where('sku', 'RHN-2042')->exists()) {
			$this->variant($drakon, 'Demoversion', 'RHN-2042', '0.00', null, 2, type: null, platforms: ['windows'], access: 'named');
		}
	}

	private function deleteMaxwell(): void
	{
		$software = DB::table('software')->where('title->de', 'Maxwell Render')->whereNull('deleted_at')->value('id');

		if ($software === null) {
			return;
		}

		$products = DB::table('licence_products')->where('software_id', $software)->pluck('id');

		DB::table('licence_variants')->whereIn('licence_product_id', $products)->whereNull('deleted_at')->update(['deleted_at' => now()]);
		DB::table('licence_products')->whereIn('id', $products)->whereNull('deleted_at')->update(['deleted_at' => now()]);
		DB::table('software')->where('id', $software)->update(['deleted_at' => now()]);
	}

	/** @param  array<int, string>  $platforms */
	private function variant(int $product, string $title, string $sku, string $price, ?string $compare, int $order, ?string $type = 'perpetual', array $platforms = ['windows', 'macos'], string $access = 'either'): void
	{
		DB::table('licence_variants')->insert([
			'uuid' => (string) Str::uuid(),
			'licence_product_id' => $product,
			'title' => json_encode(['de' => $title]),
			'sku' => $sku,
			'price' => $price,
			'compare_price' => $compare,
			'licence_type' => $type,
			'access' => $access,
			'platforms' => json_encode($platforms),
			'note' => null,
			'min_quantity' => null,
			'listed' => true,
			'education' => false,
			'order' => $order,
			'created_at' => now(),
			'updated_at' => now(),
		]);
	}
};
