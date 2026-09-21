<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * A course is the editorial description of a training. It is never booked
 * directly — students book an Event, which is one scheduled instance of it.
 *
 * Legacy `Course` was 394 LOC with business logic and formatting accessors
 * inline. Those move to Actions and Resources; what is left is relations,
 * casts and scopes.
 */
class Course extends Model
{
	use HasFactory;
	use HasMedia;
	use HasTranslations;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = [
		'number', 'slug', 'title', 'subtitle', 'summary',
		'short_description', 'full_description',
		'information_booking', 'information_content',
		'facts', 'fee', 'reviews', 'seo_description', 'seo_tags',
		'online', 'publish', 'order',
	];

	public $translatable = [
		'slug', 'title', 'subtitle', 'summary',
		'short_description', 'full_description',
		'information_booking', 'information_content',
		'seo_description', 'seo_tags',
	];

	protected function casts(): array
	{
		return [
			'facts' => 'array',
			'reviews' => 'array',
			'fee' => 'decimal:2',
			'online' => 'boolean',
			'publish' => 'boolean',
		];
	}

	public function events(): HasMany
	{
		return $this->hasMany(Event::class);
	}

	public function videos(): HasMany
	{
		return $this->hasMany(CourseVideo::class);
	}

	public function categories(): MorphToMany
	{
		return $this->taxonomy(Category::class);
	}

	public function levels(): MorphToMany
	{
		return $this->taxonomy(Level::class);
	}

	public function languages(): MorphToMany
	{
		return $this->taxonomy(Language::class);
	}

	public function software(): MorphToMany
	{
		return $this->taxonomy(Software::class);
	}

	public function tags(): MorphToMany
	{
		return $this->taxonomy(Tag::class);
	}

	/** @param class-string<Model> $related */
	private function taxonomy(string $related): MorphToMany
	{
		return $this->morphedByMany($related, 'taxonomy', 'course_taxonomy')
			->withoutGlobalScopes();
	}

	public function scopePublished(Builder $query): Builder
	{
		return $query->where('publish', true);
	}

	public function scopeOrdered(Builder $query): Builder
	{
		return $query->orderBy('order')->orderBy('number');
	}
}
