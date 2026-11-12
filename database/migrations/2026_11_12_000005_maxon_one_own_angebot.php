<?php

declare(strict_types=1);

use App\Models\Software;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * **Maxon One is an Angebot of its own** (#73, the client 2026-11-12: "Maxon
 * One als separates Angebot passt"), out from under CINEMA 4D, with its
 * product's categories. `database/data/licences.json` carries the same for a
 * fresh import. Eloquent for the new Angebot: [[Software]] makes the slug.
 */
return new class extends Migration
{
	public function up(): void
	{
		$product = DB::table('licence_products')->where('title->de', 'Maxon One')->whereNull('deleted_at')->first();

		if ($product === null) {
			return;
		}

		$software = DB::table('software')->where('title->de', 'Maxon One')->whereNull('deleted_at')->value('id')
			?? Software::create([
				'title' => ['de' => 'Maxon One'],
				'order' => (int) DB::table('software')->where('id', $product->software_id)->value('order'),
				'publish' => true,
			])->id;

		DB::table('licence_products')->where('id', $product->id)->update(['software_id' => $software]);

		if (! DB::table('software_category')->where('software_id', $software)->exists()) {
			DB::table('software_category')->insert(
				DB::table('licence_product_category')->where('licence_product_id', $product->id)->pluck('category_id')
					->map(fn (int $category) => ['software_id' => $software, 'category_id' => $category])->all(),
			);
		}
	}

	public function down(): void
	{
		// The product stays where it is; moving it back is a dashboard edit.
	}
};
