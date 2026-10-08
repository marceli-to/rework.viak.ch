<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\LicenceVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A variant as the dashboard's form and the product's list hold it — the
 * shape [[SaveLicenceVariantRequest]] takes back ([[05-licences]]), with the
 * product it belongs to beside it.
 *
 * @mixin LicenceVariant
 */
class LicenceVariantFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'title' => $this->getTranslation('title', 'de', false) ?: '',
			'sku' => $this->sku,
			'price' => $this->price,
			// '' is the select's empty choice (*Keiner (Demo)*); null matched no option.
			'licence_type' => $this->licence_type?->value ?? '',
			'access' => $this->access?->value ?? '',
			'platforms' => $this->platforms?->map->value->values()->all() ?? [],
			'min_quantity' => $this->min_quantity ?? '',
			'note' => $this->getTranslation('note', 'de', false) ?: '',
			'listed' => $this->listed,
			// For the list's row, not sent back: a variant's name alone is often the
			// vendor's word (*floating*), so the row also says type and use. No type
			// is a demo, as the form's *Keiner (Demo)* says.
			'labels' => array_values(array_filter([$this->licence_type?->label() ?? 'Demo', $this->access?->label()])),
			// For the title and the way back, not sent back.
			'product' => ['uuid' => $this->product->uuid, 'title' => $this->product->getTranslation('title', 'de')],
		];
	}
}
