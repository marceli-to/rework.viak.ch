<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\LicenceAccess;
use App\Enums\LicenceType;
use App\Enums\Platform;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;
use Illuminate\Support\Collection;

/**
 * The software list's filter ([[05-licences]]), the course list's twin
 * ([[CourseFilter]]): the same shape, so the same panel and the same
 * `course-filter.js` serve it. Every software on the list is rendered; the
 * query string decides which are hidden on the first paint, and the browser
 * applies the same rule to the same values after that.
 *
 * **A software matches when one of its licences does**: the categories are
 * the software's own, the rest are read off the listed licences of its
 * published products. A licence for *Einzelplatz oder Netzwerk* counts as
 * both.
 */
class SoftwareFilter
{
	/** In the order the panel draws them: the categories as links, the rest as selects. */
	public const ATTRIBUTES = ['category', 'manufacturer', 'licence_type', 'platform', 'access'];

	/** The selects, with what each says while nothing is chosen. */
	public const SELECTS = ['manufacturer' => 'Hersteller', 'licence_type' => 'Lizenztyp', 'platform' => 'Plattform', 'access' => 'Nutzung'];

	/** @var array<string, array<string, string[]>>|null */
	private ?array $facets = null;

	/**
	 * @param  Collection<int, Software>  $software
	 * @param  array<string, string|null>  $selected
	 */
	private function __construct(
		private readonly Collection $software,
		private readonly array $selected,
	) {}

	/**
	 * @param  Collection<int, Software>  $software  with `categories` and `products.manufacturer`, `products.variants` loaded
	 * @param  array<string, mixed>  $query
	 */
	public static function for(Collection $software, array $query): self
	{
		$selected = [];

		foreach (self::ATTRIBUTES as $attribute) {
			$value = trim((string) ($query[$attribute] ?? ''));
			$selected[$attribute] = $value !== '' ? $value : null;
		}

		return new self($software, $selected);
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
	 * What each software can be filtered by, keyed by its uuid.
	 *
	 * @return array<string, array<string, string[]>>
	 */
	public function facets(): array
	{
		return $this->facets ??= $this->software
			->mapWithKeys(function (Software $software): array {
				$licences = self::licences($software);

				return [$software->uuid => [
					'category' => $software->categories->pluck('uuid')->all(),
					'manufacturer' => $software->products->filter->isListed()->pluck('manufacturer.uuid')->filter()->unique()->values()->all(),
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

		return $this->software
			->filter(fn (Software $software) => $this->matches($facets[$software->uuid]))
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
	 * What the panel offers, as value => label: **only what some software on
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
			'category' => $this->software->flatMap->categories->unique('id')
				->mapWithKeys(fn ($category) => [$category->uuid => $category->getTranslation('title', $locale)])
				->sort()->all(),
			'manufacturer' => $this->software->flatMap->products->filter->isListed()->pluck('manufacturer')->filter()->unique('id')
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
	 * The licences a visitor can order: listed, of a published product. A
	 * product with none listed (MatrixGold) is not on the site, nor its maker.
	 *
	 * @return Collection<int, LicenceVariant>
	 */
	public static function licences(Software $software): Collection
	{
		return $software->products
			->filter(fn (LicenceProduct $product) => $product->publish)
			->flatMap(fn (LicenceProduct $product) => $product->variants->filter(fn (LicenceVariant $variant) => $variant->listed))
			->values();
	}
}
