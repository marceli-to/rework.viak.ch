<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\LicenceOrder;
use App\Models\LicenceOrderItem;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * The dispatch worklist: licence orders with a line still to send
 * ([[LicenceOrder::scopeOutstanding]], [[17-mcp]]). Paid or not is shown,
 * because nothing yet holds an unpaid order back (#2).
 */
#[Description('Licence orders with at least one line not yet sent to the customer: order number, customer, delivery e-mail, date, paid or not, and the lines still to send.')]
#[IsReadOnly]
class OutstandingLicenceOrders extends Tool
{
	public function handle(Request $request): Response
	{
		$orders = LicenceOrder::query()
			->outstanding()
			->with(['user', 'invoice', 'items'])
			->orderBy('created_at')
			->get();

		return Response::json([
			'count' => $orders->count(),
			'orders' => $orders->map(fn (LicenceOrder $order) => [
				'number' => $order->number,
				'customer' => $order->user?->name,
				'company' => $order->user?->company,
				'delivery_email' => $order->deliveryEmail(),
				'ordered_at' => $order->created_at?->toDateString(),
				'paid' => $order->isPaid(),
				'to_send' => $order->items
					->reject(fn (LicenceOrderItem $item) => $item->isDispatched())
					->map(fn (LicenceOrderItem $item) => [
						'description' => $item->description(),
						'sku' => $item->sku,
					])->values()->all(),
			])->values()->all(),
		]);
	}

	public function schema(JsonSchema $schema): array
	{
		return [];
	}
}
