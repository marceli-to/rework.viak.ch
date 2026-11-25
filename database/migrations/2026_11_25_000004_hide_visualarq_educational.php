<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * **VisualARQ Educational off the site** (#81, answered 2026-11-25 by VIAK:
 * Rhino 9 EDU and EDU Upgrade public, the rest hidden). Only VisualARQ 3
 * *Educational* was still listed besides the two Rhino ones.
 * `database/data/licences.json` carries the same for a fresh import.
 */
return new class extends Migration
{
	public function up(): void
	{
		DB::table('licence_variants')->where('sku', 'RHN-2008')->update(['listed' => false]);
	}

	public function down(): void
	{
		DB::table('licence_variants')->where('sku', 'RHN-2008')->update(['listed' => true]);
	}
};
