<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasTestimonials;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\IsTaxonomy;
use App\Support\Search\IndexedForSearch;
use App\Support\SiteUrl;
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
	use IndexedForSearch;
	use IsTaxonomy;
	use SoftDeletes;

	protected $table = 'software';

	protected $fillable = [
		'slug', 'title', 'subtitle', 'short_description', 'full_description',
		'information', 'licence_note', 'seo_description', 'seo_tags',
		'order', 'publish', 'featured',
	];

	public $translatable = [
		'slug', 'title', 'subtitle', 'short_description', 'full_description',
		'information', 'licence_note', 'seo_description', 'seo_tags',
	];

	protected function casts(): array
	{
		return ['publish' => 'boolean', 'featured' => 'boolean'];
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

	/** The plugins that run in it ([[LicenceProduct::hosts]]). */
	public function hostOf(): BelongsToMany
	{
		return $this->belongsToMany(LicenceProduct::class, 'licence_product_host');
	}

	/** The licence products sold under it. */
	public function products(): HasMany
	{
		return $this->hasMany(LicenceProduct::class);
	}

	/**
	 * The Vorhaben it is picked for, in their order: the chips on the Angebot
	 * page ([[14-angebote]]). The other side of [[Project::software]], which
	 * is where it is edited.
	 */
	public function projects(): BelongsToMany
	{
		return $this->belongsToMany(Project::class, 'project_software')->published()->ordered();
	}

	/**
	 * *Kurs ab CHF …* on the Angebot tile ([[14-angebote]]): the lowest fee of
	 * a Veranstaltung that can be booked, of a published course for this
	 * software. Null, *Kurs auf Anfrage*, when there is none. A free one
	 * (`free_of_charge`, a 0 fee) does not count: *ab CHF 0* would be wrong.
	 *
	 * Whether a course with no date counts too is #52; if it does, this is
	 * the course's own fee as well.
	 */
	public function courseFromPrice(): ?string
	{
		return Event::query()
			->published()->active()->upcoming()
			->whereHas('course', fn (Builder $courses) => $courses->published()
				->whereHas('software', fn (Builder $software) => $software->whereKey($this->getKey())))
			->with('course')
			->get()
			->filter(fn (Event $event) => $event->isBookable())
			->map(fn (Event $event) => $event->fee())
			->filter(fn (string $fee) => bccomp($fee, '0', 2) > 0)
			->sort(fn (string $a, string $b) => bccomp($a, $b, 2))
			->first();
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

	/** *Beliebte Angebote* on the homepage, beside the flagged courses ([[04-content]]). */
	public function scopeFeatured(Builder $query): Builder
	{
		return $query->where('featured', true);
	}

	/**
	 * On the site, as an Angebot ([[14-angebote]]): published, with a listed
	 * licence **or** a published course. Until 2026-10-29 only the first: the
	 * menu item was the shop, so a software only courses use (Blender,
	 * SketchUp, After Effects) had no page. Now it is an Angebot tagged *Kurs*.
	 */
	public function scopeOnSite(Builder $query): Builder
	{
		return $query->published()->where(fn (Builder $either) => $either
			->whereHas('products', fn (Builder $products) => $products->listed())
			->orWhereHas('courses', fn (Builder $courses) => $courses->published()));
	}

	/**
	 * What the Angebot holds, the tag on its tile ([[14-angebote]]): *Kurs &
	 * Software*, *Software* or *Kurs*. **Derived, never set**, so it cannot
	 * go stale: a listed licence makes it Software, a published course Kurs.
	 * Null for a software with neither, which is not on the site.
	 *
	 * Reads `has_licences` / `has_courses` when [[Software::scopeWithOffer]]
	 * loaded them, so a list asks once; otherwise it asks itself.
	 */
	public function offer(): ?string
	{
		$licences = (bool) ($this->attributes['has_licences'] ?? $this->products()->listed()->exists());
		$courses = (bool) ($this->attributes['has_courses'] ?? $this->courses()->published()->exists());

		return match (true) {
			$licences && $courses => 'Kurs & Software',
			$licences => 'Software',
			$courses => 'Kurs',
			default => null,
		};
	}

	/** What [[Software::offer]] reads, for a whole list in one query. */
	public function scopeWithOffer(Builder $query): Builder
	{
		return $query->withExists([
			'products as has_licences' => fn (Builder $products) => $products->listed(),
			'courses as has_courses' => fn (Builder $courses) => $courses->published(),
		]);
	}

	/**
	 * The title's slug, numbered if another software has it already, or if
	 * it is one of the lists the Angebot sits beside under `/de/angebote`
	 * (`kurse`, `software`): a software called *Software* gets `software-2`
	 * ([[14-angebote]]).
	 *
	 * @return array<string, string>
	 */
	public static function freeSlug(string $title): array
	{
		$base = Slug::forTitles($title !== '' ? $title : 'software');
		$reserved = collect(config('site.locales'))
			->flatMap(fn (string $locale) => [SiteUrl::segment('courses', $locale), SiteUrl::segment('software', $locale)])
			->all();

		for ($n = 1; ; $n++) {
			$slug = array_map(fn (string $slug) => $n === 1 ? $slug : "{$slug}-{$n}", $base);

			if (in_array($slug['de'], $reserved, true)) {
				continue;
			}

			if (! static::withTrashed()->where('slug->de', $slug['de'])->exists()) {
				return $slug;
			}
		}
	}
}
