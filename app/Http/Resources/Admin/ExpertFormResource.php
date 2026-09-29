<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\Concerns\PersonFields;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An expert as the dashboard's form holds them — the shape
 * [[SaveExpertRequest]] takes back ([[07-dashboard]]).
 *
 * @mixin User
 */
class ExpertFormResource extends JsonResource
{
	use PersonFields;

	public function toArray(Request $request): array
	{
		$profile = $this->expertProfile;

		return [
			...$this->personFields($this->resource),
			'visible' => $profile?->visible ?? false,
			'publish' => $profile?->publish ?? false,
			'title' => $profile?->title ?? '',
			'description' => $profile?->description ?? '',

			// Read, never sent ([[useResourceForm]]).
			'name' => $this->name,
			'is_self' => $this->resource->is($request->user()),
			// Decides whether the danger zone offers *Löschen* (#16).
			'has_history' => $this->hasHistory(),
		];
	}
}
