<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * **The licence page per Angebot** (#71, the client 2026-11-12,
 * [[14-angebote]] *As built: step 5*), its texts as every `../Software/…`
 * page in the client's docs has them:
 *
 * - `licences_lead`: the *Sublead* under *{Angebot} Lizenzen*;
 * - `requirements`: *Systemvoraussetzungen*, once per Angebot, not per
 *   licence (the client);
 * - `licences_quote`: the page's own *Zitatbox* (*Unsicher, welche Lizenz die
 *   richtige ist? …*), not the Angebot page's.
 *
 * A licence's note (*Hinweis*, the block's text under its title) grows from
 * a line to a paragraph: `note` is JSON already, only its rule changes.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('software', function (Blueprint $table) {
			$table->json('licences_lead')->nullable()->after('licences_intro');
			$table->json('requirements')->nullable()->after('licences_lead');
			$table->json('licences_quote')->nullable()->after('requirements');
		});
	}

	public function down(): void
	{
		Schema::table('software', fn (Blueprint $table) => $table->dropColumn(['licences_lead', 'requirements', 'licences_quote']));
	}
};
