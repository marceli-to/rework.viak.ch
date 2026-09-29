<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An expert as the dashboard's form and list hold them — the shape
 * [[SaveExpertRequest]] takes back ([[07-dashboard]]).
 *
 * @mixin User
 */
class ExpertFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$profile = $this->expertProfile;

		return [
			'uuid' => $this->uuid,
			'gender' => $this->gender?->value ?? '',
			'first_name' => $this->first_name,
			'last_name' => $this->last_name,
			'company' => $this->company ?? '',
			'email' => $this->email,
			'phone' => $this->phone ?? '',
			'street' => $this->street ?? '',
			'street_no' => $this->street_no ?? '',
			'zip' => $this->zip ?? '',
			'city' => $this->city ?? '',
			'country' => $this->country_code ?? '',
			'subscribe_newsletter' => $this->subscribe_newsletter,
			'visible' => $profile?->visible ?? false,
			'publish' => $profile?->publish ?? false,
			'roles' => collect(Role::cases())->filter(fn (Role $role) => $this->hasRole($role))->map->value->values()->all(),
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
