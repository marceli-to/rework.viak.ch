<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenceAccess;
use App\Enums\LicenceType;
use App\Enums\Platform;
use App\Models\Concerns\HasUuid;
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
 */
class LicenceVariant extends Model
{
	use HasFactory;
	use HasTranslations;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = ['licence_product_id', 'title', 'sku', 'price', 'licence_type', 'access', 'platforms', 'note', 'min_quantity', 'listed', 'order'];

	/** @var array<int, string> */
	public $translatable = ['title', 'note'];

	protected function casts(): array
	{
		return [
			'price' => 'decimal:2',
			'licence_type' => LicenceType::class,
			'access' => LicenceAccess::class,
			'platforms' => AsEnumCollection::of(Platform::class),
			'min_quantity' => 'integer',
			'listed' => 'boolean',
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
		$title = (string) $this->getTranslation('title', 'de', false);

		if (! $this->licence_type) {
			return $title;
		}

		$rest = collect(explode(',', $title))
			->map(fn (string $part) => trim(Str::replace(self::SAID_ELSEWHERE, '', $part, caseSensitive: false)))
			->filter()
			->implode(', ');

		return collect([$rest, $this->licence_type->label(), $this->access?->label()])->filter()->implode(', ');
	}

	public function product(): BelongsTo
	{
		return $this->belongsTo(LicenceProduct::class, 'licence_product_id');
	}
}
