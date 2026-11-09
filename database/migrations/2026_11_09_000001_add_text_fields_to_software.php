<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Angebot page's new, optional texts (#70, the client 2026-11-06,
 * [[14-angebote]] *The website texts*): every overview in the client's
 * website texts has them, in this order.
 *
 * - `audiences`: *Zielgruppen*, one line (*Architektur- & Ingenieurbüros,
 *   Yachtdesign, …*);
 * - `intro`: the sentence under it, leading into the sections (*Die
 *   Visualisierungs-Akademie bietet … an:*);
 * - `courses_intro`, `licences_intro`: a paragraph at the head of *Kurse*
 *   and of *Lizenzen*;
 * - `quote`: the quote box.
 *
 * Translatable, as the columns beside them are.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('software', function (Blueprint $table) {
			$table->json('audiences')->nullable()->after('short_description');
			$table->json('intro')->nullable()->after('audiences');
			$table->json('courses_intro')->nullable()->after('intro');
			$table->json('licences_intro')->nullable()->after('courses_intro');
			$table->json('quote')->nullable()->after('licences_intro');
		});
	}

	public function down(): void
	{
		Schema::table('software', function (Blueprint $table) {
			$table->dropColumn(['audiences', 'intro', 'courses_intro', 'licences_intro', 'quote']);
		});
	}
};
