<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\LicenceOrder;
use App\Models\LicenceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A licence order's own page ([[05-licences]]): who, where the licences go,
 * the invoice, and each line with who sent it and when.
 *
 * @mixin LicenceOrder
 */
class LicenceOrderResource extends JsonResource
{
	/** `free` (no invoice, #35), `paid` or `open`. */
	public static function payment(LicenceOrder $order): string
	{
		return match (true) {
			$order->invoice_id === null => 'free',
			$order->isPaid() => 'paid',
			default => 'open',
		};
	}

	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'number' => $this->number,
			'date' => $this->created_at?->toDateString(),
			'customer' => [
				'uuid' => $this->user->uuid,
				'name' => $this->user->name,
				'city' => $this->user->city,
				'email' => $this->user->email,
			],
			'delivery_email' => $this->deliveryEmail(),
			'entered_by' => $this->enteredBy?->name,
			'payment' => self::payment($this->resource),
			'invoice' => $this->invoice ? [
				'uuid' => $this->invoice->uuid,
				'number' => $this->invoice->number,
				'status' => $this->invoice->status->value,
				'grand_total' => (string) $this->invoice->grand_total,
				'document' => $this->invoice->document ? route('documents.show', $this->invoice->document) : null,
			] : null,
			'items' => $this->items->map(fn (LicenceOrderItem $item) => [
				'uuid' => $item->uuid,
				'title' => $item->title,
				'sku' => $item->sku,
				'host' => $item->host,
				'quantity' => $item->quantity,
				'price' => (string) $item->price,
				'net' => $item->net(),
				'free_line' => $item->licence_variant_id === null && $item->sku === null,
				'dispatched_at' => $item->dispatched_at?->toIso8601String(),
				'dispatched_by' => $item->dispatcher?->name,
			])->all(),
		];
	}
}
