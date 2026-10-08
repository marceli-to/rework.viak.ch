<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\LicenceOrder;
use App\Models\LicenceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of *Bestellungen* ([[05-licences]]): number, date, customer, what
 * was ordered, and the two things the worklist acts on, whether it is paid and
 * how many lines are still to send.
 *
 * @mixin LicenceOrder
 */
class LicenceOrderRowResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'number' => $this->number,
			'date' => $this->created_at?->toDateString(),
			'customer' => ['name' => $this->user->name, 'city' => $this->user->city],
			'lines' => $this->items->map(fn (LicenceOrderItem $item) => $item->description())->all(),
			'total' => $this->invoice ? (string) $this->invoice->grand_total : '0.00',
			'payment' => LicenceOrderResource::payment($this->resource),
			'open' => $this->items->whereNull('dispatched_at')->count(),
		];
	}
}
