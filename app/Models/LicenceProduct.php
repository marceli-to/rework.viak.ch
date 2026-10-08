<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
	use SoftDeletes;

	protected $fillable = ['software_id', 'manufacturer_id', 'title', 'slug', 'description', 'hosts', 'three_years_on_request', 'publish', 'order'];

	/** @var array<int, string> */
	public $translatable = ['title', 'slug', 'description'];

	protected function casts(): array
	{
		return [
			'hosts' => 'array',
			'three_years_on_request' => 'boolean',
			'publish' => 'boolean',
			'order' => 'integer',
		];
	}

	public function software(): BelongsTo
	{
		return $this->belongsTo(Software::class);
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

	public function scopeOrdered(Builder $query): Builder
	{
		return $query->orderBy('order')->orderBy('id');
	}
}
