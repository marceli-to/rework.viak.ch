<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** The shape a course takes when it appears inside something else. @mixin Course */
class CourseSummaryResource extends JsonResource
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
			'fee' => (string) $this->fee,
			'online' => $this->online,
		];
	}
}
