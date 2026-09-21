<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

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

	/** The one image `og:image` points at, where an editor chose a separate one. */
	public function openGraph(): ?Media
	{
		return $this->media->firstWhere('is_og', true);
	}

	/**
	 * The images that illustrate the thing itself — legacy's `type = 'visual'`,
	 * which is every row that is neither the teaser nor the Open Graph crop.
	 *
	 * Spelled as a method rather than left to call sites because the schema
	 * says what an image is **not**: two booleans replaced legacy's `type`
	 * column, and `->where('is_teaser', false)->where('is_og', false)` reads
	 * like a mistake wherever it appears.
	 *
	 * @return Collection<int, Media>
	 */
	public function visuals(): Collection
	{
		return $this->media
			->reject(fn (Media $media) => $media->is_teaser || $media->is_og)
			->values();
	}
}
