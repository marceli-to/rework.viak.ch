<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A licence product as the dashboard's form holds it — the shape
 * [[SaveLicenceProductRequest]] takes back ([[05-licences]]). The list reads
 * the same rows with `group`, `maker`, `from` and `listed` beside them.
 *
 * @mixin LicenceProduct
 */
class LicenceProductFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'software' => $this->software->uuid,
			'manufacturer' => $this->manufacturer->uuid,
			'title' => $this->getTranslation('title', 'de', false) ?: '',
			'description' => $this->getTranslation('description', 'de', false) ?: '',
			'hosts' => $this->hosts->pluck('uuid')->all(),
			'three_years_on_request' => $this->three_years_on_request,
			'publish' => $this->publish,
			'variants' => $this->variants->map(fn (LicenceVariant $variant) => [
				'uuid' => $variant->uuid,
				'title' => $variant->getTranslation('title', 'de', false) ?: '',
				'sku' => $variant->sku,
				'price' => $variant->price,
				'licence_type' => $variant->licence_type?->value,
				'access' => $variant->access?->value,
				'platforms' => $variant->platforms?->map->value->values()->all() ?? [],
				'note' => $variant->getTranslation('note', 'de', false) ?: '',
				'min_quantity' => $variant->min_quantity ?? '',
				'listed' => $variant->listed,
			])->values()->all(),
			// For the list, not sent back.
			'group' => $this->software->getTranslation('title', 'de'),
			'maker' => $this->manufacturer->getTranslation('title', 'de'),
			'from' => $this->fromPrice(),
			'listed' => $this->isListed(),
		];
	}
}
