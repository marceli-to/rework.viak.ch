<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Support\Search\IndexedForSearch;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * A licence product, *V-Ray* or *Bongo 2* ([[05-licences]]): what the site
 * shows with one dropdown of its variants. It sits in a software group, the
 * row courses already hang off, so a software page can list both.
 *
 * Soft-deleted, because an order will point at its variants and keep its own
 * frozen title and price.
 */
class LicenceProduct extends Model
{
	use HasFactory;
	use HasTranslations;
	use HasUuid;
	use IndexedForSearch;
	use SoftDeletes;

	protected $fillable = ['software_id', 'manufacturer_id', 'title', 'slug', 'description', 'features', 'three_years_on_request', 'publish', 'order'];

	/** @var array<int, string> */
	public $translatable = ['title', 'slug', 'description', 'features'];

	protected function casts(): array
	{
		return [
			'three_years_on_request' => 'boolean',
			'publish' => 'boolean',
			'order' => 'integer',
		];
	}

	public function software(): BelongsTo
	{
		return $this->belongsTo(Software::class);
	}

	/**
	 * The programs a plugin runs in, picked from the Software list (Maxwell V5,
	 * the RealFlow Plugin): an order for it names one. None for anything else.
	 */
	public function hosts(): BelongsToMany
	{
		return $this->belongsToMany(Software::class, 'licence_product_host');
	}

	/**
	 * The hosts' names, A to Z: what an order line offers and freezes.
	 *
	 * @return array<int, string>
	 */
	public function hostNames(): array
	{
		return $this->hosts
			->map(fn (Software $host) => (string) $host->getTranslation('title', 'de'))
			->sort(SORT_NATURAL | SORT_FLAG_CASE)
			->values()
			->all();
	}

	/**
	 * Its own categories, the client's five ([[14-angebote]]): a plugin sold
	 * under Rhinoceros need not share Rhino's. Seeded from its software by
	 * `catalogue:categories`, then the dashboard's.
	 */
	public function categories(): BelongsToMany
	{
		return $this->belongsToMany(Category::class, 'licence_product_category');
	}

	public function manufacturer(): BelongsTo
	{
		return $this->belongsTo(Manufacturer::class);
	}

	/** The dropdown, in its order — the hidden ones included. */
	public function variants(): HasMany
	{
		return $this->hasMany(LicenceVariant::class)->orderBy('order')->orderBy('id');
	}

	/**
	 * On the site at all: published, and at least one variant listed. A
	 * product whose variants are all hidden (MatrixGold, the Education
	 * Collections) is only ever picked by an admin.
	 */
	public function isListed(): bool
	{
		return $this->publish && $this->variants->contains(fn (LicenceVariant $variant) => $variant->listed);
	}

	/**
	 * The ticked lines on the Angebot's licence row ([[14-angebote]]): one per
	 * line in the dashboard's field, blank lines dropped.
	 *
	 * @return list<string>
	 */
	public function featureList(?string $locale = null): array
	{
		$text = (string) $this->getTranslation('features', $locale ?? app()->getLocale(), false);

		return array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: []), 'strlen'));
	}

	/** "ab CHF …": the cheapest listed variant that costs something; a demo is not a price. */
	public function fromPrice(): ?string
	{
		return $this->variants
			->filter(fn (LicenceVariant $variant) => $variant->listed && bccomp($variant->price, '0', 2) > 0)
			->sortBy(fn (LicenceVariant $variant) => (float) $variant->price)
			->first()
			?->price;
	}

	/**
	 * The title's slug, numbered if another product has it already. Made once,
	 * when the product is created: a renamed product keeps its URL.
	 *
	 * @return array<string, string>
	 */
	public static function freeSlug(string $title): array
	{
		$base = Slug::forTitles($title);

		for ($n = 1; ; $n++) {
			$slug = array_map(fn (string $slug) => $n === 1 ? $slug : "{$slug}-{$n}", $base);

			if (! static::withTrashed()->where('slug->de', $slug['de'])->exists()) {
				return $slug;
			}
		}
	}

	public function scopePublished(Builder $query): Builder
	{
		return $query->where('publish', true);
	}

	/** What [[LicenceProduct::isListed]] says of one, as a query. */
	public function scopeListed(Builder $query): Builder
	{
		return $query->published()->whereHas('variants', fn (Builder $variants) => $variants->where('listed', true));
	}

	public function scopeOrdered(Builder $query): Builder
	{
		return $query->orderBy('order')->orderBy('id');
	}
}
