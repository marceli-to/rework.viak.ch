<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;
use Illuminate\Database\Eloquent\Builder;

/**
 * Where an old 3d-software.ch URL goes ([[05-licences]] *This replaces
 * 3d-software.ch*; the client's page tree of 2026-11-12, `.rework/Deploy.md`).
 *
 * `database/data/shop-redirects.json` maps each old path (lower case, no
 * trailing slash) to a chain of targets, the first that is on the site wins:
 *
 * - `sku:RHN-1003`: the licence page of the product holding that article
 *   number. Resolved now, not when the map was written, so a product VIAK
 *   adds later (Rhino 9) catches its old URL by itself, and a renamed slug
 *   does not break it;
 * - `licence:drakon`: a licence page by slug;
 * - `software:rhinoceros`: an Angebot page;
 * - `path:/de/kontakt`: a fixed page.
 *
 * **A path the map does not name, or whose chain finds nothing, goes to the
 * software list**: the old shop's place on the new site. Maxwell's pages are
 * not in the map (the client: deactivate Maxwell, ignore it for the
 * redirects), so they land there too.
 */
class ShopRedirects
{
	/** @var array<string, string>|null */
	private static ?array $map = null;

	/**
	 * `via` is the alternative that hit; `fallback` when it was not the first.
	 *
	 * @return array{target: string, via: string, fallback: bool}
	 */
	public static function resolve(string $path): array
	{
		$chain = self::map()[self::normal($path)] ?? null;

		if ($chain === null) {
			return ['target' => SiteUrl::softwareIndex('de'), 'via' => 'unmapped', 'fallback' => true];
		}

		foreach (explode('|', $chain) as $position => $alternative) {
			if ($target = self::target($alternative)) {
				return ['target' => $target, 'via' => $alternative, 'fallback' => $position > 0];
			}
		}

		return ['target' => SiteUrl::softwareIndex('de'), 'via' => 'nothing in the chain', 'fallback' => true];
	}

	/** @return array<string, string> */
	public static function map(): array
	{
		return self::$map ??= json_decode(
			(string) file_get_contents(database_path('data/shop-redirects.json')),
			true,
			flags: JSON_THROW_ON_ERROR,
		);
	}

	public static function normal(string $path): string
	{
		$path = rtrim(mb_strtolower('/'.ltrim($path, '/')), '/');

		return $path === '' ? '/' : $path;
	}

	private static function target(string $alternative): ?string
	{
		[$kind, $value] = explode(':', $alternative, 2);

		return match ($kind) {
			'sku' => self::bySku($value),
			'licence' => self::licence(self::products()->where('slug->de', $value)->first()),
			'software' => Software::query()->onSite()->where('slug->de', $value)->exists() ? SiteUrl::software($value, 'de') : null,
			'path' => $value,
			default => throw new \UnexpectedValueException("Unknown target «{$alternative}»."),
		};
	}

	private static function bySku(string $sku): ?string
	{
		$variant = LicenceVariant::query()
			->where('sku', $sku)
			->whereHas('product', fn (Builder $product) => $product->listed()->whereHas('software', fn (Builder $software) => $software->published()))
			->with('product')
			->first();

		return self::licence($variant?->product);
	}

	/** @return Builder<LicenceProduct> as the licence page finds them ([[LicenceController::onSite]]) */
	private static function products(): Builder
	{
		return LicenceProduct::query()->listed()->whereHas('software', fn (Builder $software) => $software->published());
	}

	private static function licence(?LicenceProduct $product): ?string
	{
		return $product ? SiteUrl::licence($product->getTranslation('slug', 'de'), 'de') : null;
	}
}
