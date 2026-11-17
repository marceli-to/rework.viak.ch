<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * **EDU public** (#75, answered 2026-11-17 by Oliver: "public"): Rhino 9 EDU,
 * Rhino 9 EDU Upgrade and VisualARQ Educational back in the shop, as #70 had
 * them, undoing the software list's *N* of 2026-11-12. They stay
 * `education`, so no *ab CHF* reads their price ([[LicenceVariant]]).
 * `database/data/licences.json` carries the same for a fresh import.
 */
return new class extends Migration
{
	public function up(): void
	{
		DB::table('licence_variants')->whereIn('sku', ['RHN-1091', 'RHN-1092', 'RHN-2008'])->update(['listed' => true]);
	}

	public function down(): void
	{
		DB::table('licence_variants')->whereIn('sku', ['RHN-1091', 'RHN-1092', 'RHN-2008'])->update(['listed' => false]);
	}
};
