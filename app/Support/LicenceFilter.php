<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\LicenceAccess;
use App\Enums\LicenceType;
use App\Enums\Platform;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use Illuminate\Support\Collection;

/**
 * The licence list's filter ([[05-licences]], [[14-angebote]]), the course
 * list's twin ([[CourseFilter]]): the same shape, so the same panel and the
 * same `course-filter.js` serve it. Every product on the list is rendered;
 * the query string decides which are hidden on the first paint, and the
 * browser applies the same rule to the same values after that.
 *
 * **A product matches when one of its listed licences does**; the
 * categories and the maker are the product's own. A licence for *Einzelplatz
 * oder Netzwerk* counts as both. Until 2026-10-29 this was the software
 * list's, read across a software's products; the list moved to the product
 * level with the client's two levels.
 */
class LicenceFilter
{
	/** In the order the panel draws them: the categories as links, the rest as selects. */
	public const ATTRIBUTES = ['category', 'manufacturer', 'licence_type', 'platform', 'access'];

	/** The selects, with what each says while nothing is chosen. */
	public const SELECTS = ['manufacturer' => 'Hersteller', 'licence_type' => 'Lizenztyp', 'platform' => 'Plattform', 'access' => 'Nutzung'];

	/** @var array<string, array<string, string[]>>|null */
	private ?array $facets = null;

	/**
	 * @param  Collection<int, LicenceProduct>  $products
	 * @param  array<string, string|null>  $selected
	 */
	private function __construct(
		private readonly Collection $products,
		private readonly array $selected,
	) {}

	/**
	 * @param  Collection<int, LicenceProduct>  $products  listed, with `categories`, `manufacturer` and `variants` loaded
	 * @param  array<string, mixed>  $query
	 */
	public static function for(Collection $products, array $query): self
	{
		$selected = [];

		foreach (self::ATTRIBUTES as $attribute) {
			$value = trim((string) ($query[$attribute] ?? ''));
			$selected[$attribute] = $value !== '' ? $value : null;
		}

		return new self($products, $selected);
	}

	/** @return array<string, string|null> */
	public function selected(): array
	{
		return $this->selected;
	}

	/** @return array<string, string> `''` for nothing chosen, as a select has it */
	public function seed(): array
	{
		return array_map(fn (?string $value) => $value ?? '', $this->selected);
	}

	/**
	 * What each product can be filtered by, keyed by its uuid.
	 *
	 * @return array<string, array<string, string[]>>
	 */
	public function facets(): array
	{
		return $this->facets ??= $this->products
			->mapWithKeys(function (LicenceProduct $product): array {
				$licences = self::licences($product);

				return [$product->uuid => [
					'category' => $product->categories->pluck('uuid')->all(),
					'manufacturer' => array_values(array_filter([$product->manufacturer?->uuid])),
					'licence_type' => $licences->map(fn (LicenceVariant $variant) => $variant->licence_type?->value)->filter()->unique()->values()->all(),
					'platform' => $licences->flatMap(fn (LicenceVariant $variant) => $variant->platforms?->map->value ?? [])->unique()->values()->all(),
					'access' => $licences->flatMap(fn (LicenceVariant $variant) => match ($variant->access) {
						LicenceAccess::Either => [LicenceAccess::Named->value, LicenceAccess::Floating->value],
						null => [],
						default => [$variant->access->value],
					})->unique()->values()->all(),
				]];
			})
			->all();
	}

	/**
	 * The uuids the query string selects — everything when it selects nothing.
	 *
	 * @return string[]
	 */
	public function matching(): array
	{
		$facets = $this->facets();

		return $this->products
			->filter(fn (LicenceProduct $product) => $this->matches($facets[$product->uuid]))
			->pluck('uuid')
			->all();
	}

	/** @param  array<string, string[]>  $facets */
	public function matches(array $facets): bool
	{
		foreach ($this->selected as $attribute => $value) {
			if ($value !== null && ! in_array($value, $facets[$attribute], true)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * What the panel offers, as value => label: **only what some product on
	 * the page carries**, as the course filter has it, so every option leads
	 * somewhere.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function options(): array
	{
		$locale = app()->getLocale();
		$facets = collect($this->facets());
		$present = fn (string $attribute) => $facets->pluck($attribute)->flatten()->unique()->all();

		return [
			'category' => $this->products->flatMap->categories->unique('id')->sortBy('order')
				->mapWithKeys(fn ($category) => [$category->uuid => $category->getTranslation('title', $locale)])
				->all(),
			'manufacturer' => $this->products->pluck('manufacturer')->filter()->unique('id')
				->mapWithKeys(fn ($maker) => [$maker->uuid => $maker->getTranslation('title', $locale)])
				->sort()->all(),
			'licence_type' => collect(LicenceType::cases())->filter(fn (LicenceType $type) => in_array($type->value, $present('licence_type'), true))
				->mapWithKeys(fn (LicenceType $type) => [$type->value => $type->label()])->all(),
			'platform' => collect(Platform::cases())->filter(fn (Platform $platform) => in_array($platform->value, $present('platform'), true))
				->mapWithKeys(fn (Platform $platform) => [$platform->value => $platform->label()])->all(),
			// The two a visitor chooses between; *either* is in both.
			'access' => collect(['named' => 'Einzelplatz', 'floating' => 'Netzwerk'])
				->filter(fn (string $label, string $value) => in_array($value, $present('access'), true))->all(),
		];
	}

	/**
	 * The licences a visitor can order: the listed ones.
	 *
	 * @return Collection<int, LicenceVariant>
	 */
	public static function licences(LicenceProduct $product): Collection
	{
		return $product->variants->filter(fn (LicenceVariant $variant) => $variant->listed)->values();
	}
}
