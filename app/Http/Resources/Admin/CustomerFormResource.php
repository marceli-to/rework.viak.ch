<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\Concerns\PersonFields;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A student as the dashboard's form holds them — the shape
 * [[SaveCustomerRequest]] takes back ([[07-dashboard]]).
 *
 * @mixin User
 */
class CustomerFormResource extends JsonResource
{
	use PersonFields;

	public function toArray(Request $request): array
	{
		return [
			...$this->personFields($this->resource),
			'addresses' => $this->addresses->map(fn (UserAddress $address) => [
				'uuid' => $address->uuid,
				'first_name' => $address->first_name ?? '',
				'last_name' => $address->last_name ?? '',
				'company' => $address->company ?? '',
				'street' => $address->street ?? '',
				'street_no' => $address->street_no ?? '',
				'zip' => $address->zip ?? '',
				'city' => $address->city ?? '',
				'country' => $address->country_code ?? '',
			])->all(),

			// Read, never sent ([[useResourceForm]]).
			'name' => $this->name,
			'is_self' => $this->resource->is($request->user()),
			// A changed address waits for the person to confirm it.
			'email_verified' => $this->hasVerifiedEmail(),
			'deactivated_at' => $this->deactivated_at?->toIso8601String(),
		];
	}
}
