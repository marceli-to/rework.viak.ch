<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * **Maxwell Render is deactivated** (the client, 2026-11-12, with the old
 * shop's page tree: "Du kannst die Produkte Maxwell in der neuen Umgebung
 * deaktivieren und für das Redirecting somit ignorieren"). The Angebot is
 * unpublished, which takes its products off the site with it; nothing is
 * deleted, so VIAK can publish it again in the dashboard.
 * `database/data/licences.json` carries it for a fresh import.
 */
return new class extends Migration
{
	public function up(): void
	{
		DB::table('software')->where('title->de', 'Maxwell Render')->update(['publish' => false]);
	}

	public function down(): void
	{
		DB::table('software')->where('title->de', 'Maxwell Render')->update(['publish' => true]);
	}
};
