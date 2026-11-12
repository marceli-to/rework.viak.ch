<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * **The *Was ist …?* page's subtitles are headings** (Marcel, 2026-11-12):
 * the editor has H2 and H3 for it now, instead of the hint «Zwischentitel
 * fett, als eigene Zeile». Every paragraph that is nothing but bold
 * (*Woher kommt Rhino?*) becomes an `<h2>`; the page draws it as it drew the
 * bold line. `database/data/texts.json` and the seeder carry the same.
 */
return new class extends Migration
{
	public function up(): void
	{
		DB::table('software')->whereNotNull('full_description')->get(['id', 'full_description'])->each(function (object $row): void {
			$texts = json_decode((string) $row->full_description, true);

			if (! is_array($texts)) {
				return;
			}

			$headed = array_map(fn ($html) => is_string($html) ? preg_replace('#<p><strong>([^<]*)</strong></p>#u', '<h2>$1</h2>', $html) : $html, $texts);

			if ($headed !== $texts) {
				DB::table('software')->where('id', $row->id)->update(['full_description' => json_encode($headed, JSON_UNESCAPED_UNICODE)]);
			}
		});
	}

	public function down(): void
	{
		DB::table('software')->whereNotNull('full_description')->get(['id', 'full_description'])->each(function (object $row): void {
			$texts = json_decode((string) $row->full_description, true);

			if (is_array($texts)) {
				DB::table('software')->where('id', $row->id)->update(['full_description' => json_encode(
					array_map(fn ($html) => is_string($html) ? preg_replace('#<h2>([^<]*)</h2>#u', '<p><strong>$1</strong></p>', $html) : $html, $texts),
					JSON_UNESCAPED_UNICODE,
				)]);
			}
		});
	}
};
