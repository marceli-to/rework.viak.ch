<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Anything that can carry uploads ([[08-accounts]]).
 *
 * One relation replaces legacy's two: `images` reached its owner through a
 * `nullableMorphs('imageable')` and `files` through a `fileables` pivot, which
 * meant two ways to ask the same question and two places to get it wrong.
 */
trait HasMedia
{
	public function media(): MorphMany
	{
		return $this->morphMany(Media::class, 'mediable')->ordered();
	}

	/** The one image a card or a teaser uses. */
	public function teaser(): ?Media
	{
		return $this->media->firstWhere('is_teaser', true) ?? $this->media->first();
	}
}
