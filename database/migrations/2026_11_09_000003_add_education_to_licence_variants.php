<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A variant can be an **Ausbildungslizenz** (Marcel, 2026-11-09): since the
 * EDU licences are sold on the site (#70), Rhinoceros read *ab CHF 90*, its
 * EDU Upgrade, which is no price a company pays. *Ab CHF …* now leaves them
 * out ([[LicenceProduct::fromPrice]]); they still sell at their own price.
 *
 * Set here for every EDU, lab and university licence of the catalogue,
 * listed or not, as `database/data/licences.json` marks them. VIAK ticks it
 * in the variant form for new ones.
 */
return new class extends Migration
{
	private const SKUS = [
		'RHN-1091', 'RHN-1092', 'RHN-1093', 'RHN-1094', 'RHN-2008',
		'VRY-2401', 'VRY-2402', 'VRY-2403', 'VRY-2404', 'VRY-2405',
		'ENC-1201', 'ENC-1202',
	];

	public function up(): void
	{
		Schema::table('licence_variants', function (Blueprint $table) {
			$table->boolean('education')->default(false)->after('listed');
		});

		DB::table('licence_variants')->whereIn('sku', self::SKUS)->update(['education' => true]);
	}

	public function down(): void
	{
		Schema::table('licence_variants', fn (Blueprint $table) => $table->dropColumn('education'));
	}
};
