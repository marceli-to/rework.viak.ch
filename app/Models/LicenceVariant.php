<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenceAccess;
use App\Enums\LicenceType;
use App\Enums\Platform;
use App\Models\Concerns\HasUuid;
use App\Support\Search\IndexedForSearch;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * One entry in a product's dropdown, and one row of the client's list
 * ([[05-licences]]): *Solo named*, *Update*, *5 Stück*, *Demoversion*. Its own
 * article number and its own **net** price; VAT is added on the invoice line.
 *
 * Updates, upgrades and demos are variants too (#37): "konsequent
 * Produktvarianten als Dropdown". A demo costs 0 and is ordered like the rest
 * (#35). `listed = false` keeps a variant off the site (#36).
 *
 * `compare_price` is a **Streichpreis** (the client, 2026-11-12): shown struck
 * through beside the price, as the old shop showed its Rhino 9 promo. Only
 * shown; the price is what is charged.
 */
class LicenceVariant extends Model
{
	use HasFactory;
	use HasTranslations;
	use HasUuid;
	use IndexedForSearch;
	use SoftDeletes;

	protected $fillable = ['licence_product_id', 'title', 'sku', 'price', 'compare_price', 'licence_type', 'access', 'platforms', 'note', 'min_quantity', 'listed', 'education', 'order'];

	/** @var array<int, string> */
	public $translatable = ['title', 'note'];

	protected function casts(): array
	{
		return [
			'price' => 'decimal:2',
			'compare_price' => 'decimal:2',
			'licence_type' => LicenceType::class,
			'access' => LicenceAccess::class,
			'platforms' => AsEnumCollection::of(Platform::class),
			'min_quantity' => 'integer',
			'listed' => 'boolean',
			'education' => 'boolean',
			'order' => 'integer',
		];
	}

	/**
	 * The words a client's variant name uses for what `licence_type` and
	 * `access` already say: dropped from the shop's label, which says it in
	 * German instead. Longest first, so *named user* goes before *named*.
	 */
	private const SAID_ELSEWHERE = ['named user', 'node-locked', 'floating', 'named', 'Jahresmietlizenz'];

	/**
	 * What the shop's dropdown shows (Marcel, 2026-10-08): about twenty names
	 * in the client's list are only the vendor's word (*floating*), so the
	 * label is built from Lizenztyp and Nutzung, with what is left of the
	 * name in front. *Teams, named user* becomes *Teams, Jahresmietlizenz,
	 * Einzelplatz (named)*; *floating* becomes *Jahresmietlizenz, Netzwerk
	 * (floating)*. A demo has no Lizenztyp and keeps its name.
	 */
	public function shopLabel(): string
	{
		return collect([$this->shopName(), $this->shopTerms()])->filter()->implode(', ');
	}

	/**
	 * The label's first half: what is left of the client's name once the
	 * words [[LicenceVariant::shopTerms]] says are gone (*Solo*, *1 Stück*,
	 * *Upgrade (Promo)*), empty when nothing is (*floating*). A demo has no
	 * Lizenztyp and keeps its whole name.
	 */
	public function shopName(): string
	{
		$title = (string) $this->getTranslation('title', 'de', false);

		if (! $this->licence_type) {
			return $title;
		}

		return collect(explode(',', $title))
			->map(fn (string $part) => trim(Str::replace(self::SAID_ELSEWHERE, '', $part, caseSensitive: false)))
			->filter()
			->implode(', ');
	}

	/** The label's second half, Lizenztyp and Nutzung in German: *Jahresmietlizenz, Einzelplatz (named)*; nothing for a demo. */
	public function shopTerms(): string
	{
		// A demo says neither: its name is all it has (*Pro Demoversion*).
		if (! $this->licence_type) {
			return '';
		}

		return collect([$this->licence_type?->label(), $this->access?->label()])->filter()->implode(', ');
	}

	/**
	 * **A licence block's title on the licence page** (#71, built 2026-11-17),
	 * as the client's docs write it (*Corona Solo (named)*, *Corona Render
	 * Node (1 Stück)*): the product's name, what is left of the licence's, and
	 * the vendor's word for its use in brackets, *(named)* or *(floating)*,
	 * which tells *Corona Collection (named)* from *(floating)*. *Einzelplatz
	 * oder Netzwerk* (every McNeel licence) has no such word: *Rhinoceros 9
	 * Vollversion*. A demo keeps its name: *Lumion Pro Demoversion*.
	 */
	public function blockTitle(): string
	{
		$product = (string) $this->product?->getTranslation('title', app()->getLocale(), false);
		$use = $this->licence_type && $this->access && $this->access !== LicenceAccess::Either ? '('.$this->access->value.')' : '';

		return collect([$product, $this->shopName(), $use])->filter()->implode(' ');
	}

	/**
	 * The grey line under the block's title: Lizenztyp and Nutzung without the
	 * bracket the title already has, *Jahresmietlizenz, Einzelplatz*.
	 */
	public function blockTerms(): string
	{
		if (! $this->licence_type) {
			return '';
		}

		return collect([$this->licence_type->label(), $this->access ? Str::before($this->access->label(), ' (') : null])->filter()->implode(', ');
	}

	public function product(): BelongsTo
	{
		return $this->belongsTo(LicenceProduct::class, 'licence_product_id');
	}
}
