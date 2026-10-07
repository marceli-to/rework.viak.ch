<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Project;
use App\Support\SiteUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A Vorhaben as the dashboard's form and list hold it — the shape
 * [[SaveProjectRequest]] takes back ([[04-content]]).
 *
 * @mixin Project
 */
class ProjectFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$de = fn (string $field) => $this->getTranslation($field, 'de', false) ?: '';

		return [
			'uuid' => $this->uuid,
			'url' => SiteUrl::project($this->getTranslation('slug', 'de'), 'de'),
			'title' => $de('title'),
			'teaser' => $de('teaser'),
			'lead' => $de('lead'),
			'text' => $de('text'),
			'seo_description' => $de('seo_description'),
			'seo_tags' => $de('seo_tags'),
			'courses' => $this->courses->pluck('uuid')->all(),
			'publish' => $this->publish,
		];
	}
}
