<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * A Vorhaben ([[04-content]]): what a visitor wants to do, *Räume
 * visualisieren*, and the courses for it. A tile on the homepage and a page at
 * `/de/vorhaben/{slug}`. Called `Project` in code, as a Veranstaltung is an
 * `Event`; the dashboard and the site say *Vorhaben*.
 */
class Project extends Model
{
	use HasFactory;
	use HasTranslations;
	use HasUuid;

	protected $fillable = ['title', 'slug', 'teaser', 'lead', 'text', 'seo_description', 'seo_tags', 'publish', 'order'];

	/** @var array<int, string> */
	public $translatable = ['title', 'slug', 'teaser', 'lead', 'text', 'seo_description', 'seo_tags'];

	protected function casts(): array
	{
		return [
			'publish' => 'boolean',
			'order' => 'integer',
		];
	}

	/** The offer list, in the order the dashboard dragged it into. */
	public function courses(): BelongsToMany
	{
		return $this->belongsToMany(Course::class)
			->withPivot('order')
			->orderByPivot('order');
	}

	public function scopePublished(Builder $query): Builder
	{
		return $query->where('publish', true);
	}

	public function scopeOrdered(Builder $query): Builder
	{
		return $query->orderBy('order')->orderBy('id');
	}
}
