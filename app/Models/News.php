<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasUuid;
use App\Support\NewsLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * **A news item on the homepage** (Oliver, 2026-11-12, [[04-content]] *News*):
 * a small picture, a title, a line of text, an arrow to a page VIAK picks.
 * The first two published, in their order, stand beside *Nächste
 * Kurstermine*. Edited in *Seiteninhalte → News*.
 */
class News extends Model
{
	use HasFactory;
	use HasMedia;
	use HasTranslations;
	use HasUuid;

	protected $table = 'news';

	protected $fillable = ['title', 'text', 'link', 'publish', 'order'];

	/** @var array<int, string> */
	public $translatable = ['title', 'text'];

	protected function casts(): array
	{
		return [
			'publish' => 'boolean',
			'order' => 'integer',
		];
	}

	public function scopePublished(Builder $query): Builder
	{
		return $query->where('publish', true);
	}

	public function scopeOrdered(Builder $query): Builder
	{
		return $query->orderBy('order')->orderBy('id');
	}

	/** Where its arrow leads, or null: no link, or its page is off the site ([[NewsLink]]). */
	public function url(): ?string
	{
		return NewsLink::url($this->link);
	}
}
