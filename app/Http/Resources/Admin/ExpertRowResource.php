<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One line of the Experten list, as legacy's shows it: name and city, the
 * address to write to ([[07-dashboard]]).
 *
 * @mixin User
 */
class ExpertRowResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'name' => $this->name,
			'city' => $this->city,
			'email' => $this->email,
			'publish' => $this->expertProfile?->publish ?? false,
			'visible' => $this->expertProfile?->visible ?? false,
		];
	}
}
