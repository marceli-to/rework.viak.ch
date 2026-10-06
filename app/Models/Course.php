<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasTestimonials;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
	use HasTestimonials;
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

	/**
	 * The testimonials **about** this course, the ones whose subject it is.
	 * The course page's *Kundenmeinungen* shows these, published and in the
	 * testimonials' own order (Marcel, 2026-10-06, `Open-Questions.md` #18):
	 * automatic, not picked, so `testimonials()` (placements) stays unused on a
	 * course for now.
	 */
	public function testimonialsAbout(): MorphMany
	{
		return $this->morphMany(Testimonial::class, 'subject');
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

	/**
	 * *07*, not *7* — legacy's `getCourseNumberAttribute()`, and how the
	 * dashboard and the portals print it, so the titles after it line up. The
	 * column stays an integer; this is display only (`courseNumber()` in
	 * `support/format.js` is the SPA's twin).
	 */
	public function displayNumber(): string
	{
		return str_pad((string) $this->number, 2, '0', STR_PAD_LEFT);
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
