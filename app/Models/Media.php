<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An uploaded file, image or otherwise ([[08-accounts]]).
 *
 * @property array{x: int, y: int, w: int, h: int}|null $crop
 */
class Media extends Model
{
	use HasFactory;
	use HasUuid;

	protected $fillable = [
		'uuid', 'mediable_type', 'mediable_id', 'file', 'original_name',
		'mime_type', 'size', 'alt', 'caption', 'width', 'height',
		'crop', 'variant', 'is_teaser', 'is_og', 'sort_order',
	];

	protected function casts(): array
	{
		return [
			'size' => 'integer',
			'width' => 'integer',
			'height' => 'integer',
			'crop' => 'array',
			'is_teaser' => 'boolean',
			'is_og' => 'boolean',
			'sort_order' => 'integer',
		];
	}

	public function mediable(): MorphTo
	{
		return $this->morphTo();
	}

	public function isImage(): bool
	{
		return str_starts_with((string) $this->mime_type, 'image/');
	}

	/**
	 * Derived, never stored.
	 *
	 * Legacy kept an `orientation` column of 'l'/'p' and it is **wrong on 49 of
	 * the 333 live images** — measured against the files themselves on
	 * 2026-09-18. A value that disagrees with the thing it describes is worse
	 * than no value, so the column is not ported.
	 */
	public function orientation(): string
	{
		if (! $this->width || ! $this->height) {
			return 'unknown';
		}

		return match (true) {
			$this->width > $this->height => 'landscape',
			$this->height > $this->width => 'portrait',
			default => 'square',
		};
	}

	/** The aspect ratio the crop implies, falling back to the file's own. */
	public function aspectRatio(): float
	{
		if ($this->crop && ($this->crop['w'] ?? 0) > 0) {
			return $this->crop['h'] / $this->crop['w'];
		}

		return ($this->height ?: 1) / ($this->width ?: 1);
	}

	public function scopeDesktop(Builder $query): void
	{
		$query->where('variant', 'desktop');
	}

	public function scopeOrdered(Builder $query): void
	{
		$query->orderBy('sort_order')->orderBy('id');
	}
}
