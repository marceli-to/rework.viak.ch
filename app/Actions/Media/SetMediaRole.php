<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Support\Facades\DB;

/**
 * What an image is for — legacy's *Typ*: *Vorschau* (the teaser), *Hauptbild*
 * (a visual) or *OpenGraph* ([[07-dashboard]]).
 *
 * Two booleans hold it since chunk 08 replaced legacy's `type` column
 * ([[HasMedia]]), and **an owner has one teaser and one Open Graph image**, as
 * the ported data does (40 teasers on 41 courses, never two). So making one the
 * teaser takes the flag off whichever had it, rather than leaving two for a
 * page to choose between.
 */
class SetMediaRole
{
	public const ROLES = ['teaser', 'visual', 'og'];

	public function execute(Media $media, string $role): Media
	{
		DB::transaction(function () use ($media, $role): void {
			$siblings = Media::query()
				->where('mediable_type', $media->mediable_type)
				->where('mediable_id', $media->mediable_id)
				->whereKeyNot($media->getKey());

			if ($role === 'teaser') {
				(clone $siblings)->update(['is_teaser' => false]);
			}

			if ($role === 'og') {
				(clone $siblings)->update(['is_og' => false]);
			}

			$media->update(['is_teaser' => $role === 'teaser', 'is_og' => $role === 'og']);
		});

		return $media->refresh();
	}
}
