<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserAddress */
class UserAddressResource extends JsonResource
{
	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'first_name' => $this->first_name,
			'last_name' => $this->last_name,
			'company' => $this->company,
			'street' => $this->street,
			'street_no' => $this->street_no,
			'zip' => $this->zip,
			'city' => $this->city,
			'country_code' => $this->country_code,
		];
	}
}
