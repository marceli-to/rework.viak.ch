<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Illuminate\Database\Eloquent\Model */
class TaxonomyResource extends JsonResource
{
	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'title' => $this->getTranslations('title'),
		];
	}
}
