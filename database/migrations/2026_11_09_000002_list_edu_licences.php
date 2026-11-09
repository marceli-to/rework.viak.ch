<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * **EDU licences are sold on the site** (#70, the client 2026-11-06: "Mache
 * sie öffentlich. Wir passen das bedarfsweise an später"), reversing #36 for
 * them: Rhinoceros 8 *EDU* and *EDU Upgrade*, VisualARQ 3 *Educational*.
 * `database/data/licences.json` lists them too.
 *
 * The lab and university seat-band licences (Rhino's *Lab license 30
 * seats*, the V-Ray and Enscape *Education Collections*) stay off the site:
 * a school orders those, not a student. VIAK lists any of them with the
 * variant's *Im Shop bestellbar*. MatrixGold stays as it was.
 */
return new class extends Migration
{
	private const SKUS = ['RHN-1091', 'RHN-1092', 'RHN-2008'];

	public function up(): void
	{
		DB::table('licence_variants')->whereIn('sku', self::SKUS)->update(['listed' => true]);
	}

	public function down(): void
	{
		DB::table('licence_variants')->whereIn('sku', self::SKUS)->update(['listed' => false]);
	}
};
