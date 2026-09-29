<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A discount code as the dashboard's form and list hold it — the shape
 * [[SaveDiscountCodeRequest]] takes back ([[07-dashboard]]).
 *
 * @mixin DiscountCode
 */
class DiscountCodeFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'code' => $this->code,
			'amount' => (string) $this->amount,
			'type' => $this->type->value,
			'valid_from' => $this->valid_from?->toDateString() ?? '',
			'valid_to' => $this->valid_to?->toDateString() ?? '',
			'usage_limit' => $this->usage_limit ?? '',
			'remarks' => $this->remarks ?? '',

			// Read, never sent ([[useResourceForm]]).
			'times_used' => $this->timesUsed(),
			'redeemable' => $this->isRedeemableOn(today()),
		];
	}
}
