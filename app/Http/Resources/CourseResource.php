<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Course */
class CourseResource extends JsonResource
{
	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'number' => $this->number,
			'slug' => $this->getTranslations('slug'),

			'title' => $this->getTranslations('title'),
			'subtitle' => $this->getTranslations('subtitle'),
			'summary' => $this->getTranslations('summary'),
			'short_description' => $this->getTranslations('short_description'),
			'full_description' => $this->getTranslations('full_description'),
			'information_booking' => $this->getTranslations('information_booking'),
			'information_content' => $this->getTranslations('information_content'),
			'facts' => $this->facts,
			'reviews' => $this->reviews,

			'fee' => (string) $this->fee,
			'online' => $this->online,
			'publish' => $this->publish,
			'order' => $this->order,

			'seo' => [
				'description' => $this->getTranslations('seo_description'),
				'tags' => $this->getTranslations('seo_tags'),
			],

			'categories' => TaxonomyResource::collection($this->whenLoaded('categories')),
			'levels' => TaxonomyResource::collection($this->whenLoaded('levels')),
			'languages' => TaxonomyResource::collection($this->whenLoaded('languages')),
			'software' => TaxonomyResource::collection($this->whenLoaded('software')),
			'tags' => TaxonomyResource::collection($this->whenLoaded('tags')),

			'events' => EventResource::collection($this->whenLoaded('events')),
			'events_count' => $this->whenCounted('events'),
		];
	}
}
