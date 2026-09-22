<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\Basket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A priced basket, as the server sees it ([[06-bookings]]).
 *
 * Every figure here is computed server-side and none of them is accepted back.
 * The client renders `total` and hands it to the checkout only so a total that
 * has since moved can be **refused** — see [[CompleteCheckoutRequest]].
 *
 * @mixin Basket
 */
class BasketResource extends JsonResource
{
	public function __construct(private readonly Basket $basket)
	{
		parent::__construct($basket);
	}

	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'items' => collect($this->basket->items)->map(fn ($item) => [
				'event' => new EventResource($item->event),
				'rental' => $item->rental,
				'course_fee' => $item->courseFee,
				'rental_fee' => $item->rentalFee,

				// Not a price. The basket page paints the row red on it,
				// because [[CompleteCheckout]] will refuse the line.
				'booked' => $item->booked,
			])->all(),

			'discount_code' => $this->basket->discountCode?->code,
			'course_net' => $this->basket->courseNet(),
			'rental_net' => $this->basket->rentalNet(),
			'net' => $this->basket->net(),
			'discount' => $this->basket->discount,

			// Courses are VAT-exempt, so this is VAT on the laptop rentals and
			// nothing else. Do not "fix" the zero ([[InvoiceItemType]]).
			'vat' => $this->basket->vat(),
			'total' => $this->basket->total(),
		];
	}
}
