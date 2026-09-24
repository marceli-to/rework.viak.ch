<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One course on the dashboard's *Kurse* screen, with its upcoming dates
 * ([[07-dashboard]]).
 *
 * @mixin \App\Models\Course
 */
class CourseRowResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'number' => $this->number,
			'title' => $this->getTranslation('title', 'de'),
			'publish' => $this->publish,
			'events' => EventRowResource::collection($this->whenLoaded('events')),
		];
	}
}
