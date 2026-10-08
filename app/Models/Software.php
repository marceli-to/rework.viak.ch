<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasTestimonials;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\IsTaxonomy;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * A software, Rhinoceros, V-Ray, Maxwell…: what a course teaches, and since
 * chunk 05 what licence products are sold under ([[05-licences]]). The UI's
 * three levels are Software → Produkt → Lizenz.
 *
 * Since 2026-10-08 it has a page, drawn as a course page is: the same copy
 * columns by the same names, images, categories and SEO. The page lists its
 * products with their licences, and its courses.
 */
class Software extends Model
{
	use HasMedia;
	use HasTestimonials;
	use HasTranslations;
	use HasUuid;
	use IsTaxonomy;
	use SoftDeletes;

	protected $table = 'software';

	protected $fillable = [
		'slug', 'title', 'subtitle', 'short_description', 'full_description',
		'information', 'information_more', 'seo_description', 'seo_tags',
		'order', 'publish',
	];

	public $translatable = [
		'slug', 'title', 'subtitle', 'short_description', 'full_description',
		'information', 'information_more', 'seo_description', 'seo_tags',
	];

	protected function casts(): array
	{
		return ['publish' => 'boolean'];
	}

	/**
	 * The slug is made from the title once, whichever door creates it (this
	 * form, the settings list, the `+` beside the product form's select, the
	 * import), and not again: a renamed software keeps its URL.
	 */
	protected static function booted(): void
	{
		static::creating(function (Software $software): void {
			if (blank($software->getTranslation('slug', 'de', false))) {
				$software->setTranslations('slug', self::freeSlug((string) $software->getTranslation('title', 'de', false)));
			}
		});
	}

	/** The licence products sold under it. */
	public function products(): HasMany
	{
		return $this->hasMany(LicenceProduct::class);
	}

	/** The courses' categories: the software list filters by them too. */
	public function categories(): BelongsToMany
	{
		return $this->belongsToMany(Category::class, 'software_category');
	}

	/**
	 * The testimonials **about** this software, as a course's
	 * ([[Course::testimonialsAbout]]): its page's *Kundenmeinungen*.
	 */
	public function testimonialsAbout(): MorphMany
	{
		return $this->morphMany(Testimonial::class, 'subject');
	}

	/**
	 * On the site: published, with at least one product published and one of
	 * its licences listed. The menu item is the shop, so a software only
	 * courses use (SketchUp, Godot) has no page.
	 */
	public function scopeOnSite(Builder $query): Builder
	{
		return $query->published()->whereHas('products', fn (Builder $products) => $products
			->published()
			->whereHas('variants', fn (Builder $variants) => $variants->where('listed', true)));
	}

	/**
	 * The title's slug, numbered if another software has it already.
	 *
	 * @return array<string, string>
	 */
	public static function freeSlug(string $title): array
	{
		$base = Slug::forTitles($title !== '' ? $title : 'software');

		for ($n = 1; ; $n++) {
			$slug = array_map(fn (string $slug) => $n === 1 ? $slug : "{$slug}-{$n}", $base);

			if (! static::withTrashed()->where('slug->de', $slug['de'])->exists()) {
				return $slug;
			}
		}
	}
}
