<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\News;
use App\Support\NewsLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A news item as the dashboard's form and list hold it, the shape
 * [[SaveNewsRequest]] takes back. `link_label` and `url` are for the list.
 *
 * @mixin News
 */
class NewsFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$label = collect(NewsLink::options())->flatMap(fn (array $group) => $group)->get((string) $this->link);

		return [
			'uuid' => $this->uuid,
			'title' => $this->getTranslation('title', 'de', false) ?: '',
			'text' => $this->getTranslation('text', 'de', false) ?: '',
			'link' => $this->link ?? '',
			'publish' => $this->publish,
			'link_label' => $label,
			'url' => $this->resource->url(),
		];
	}
}
