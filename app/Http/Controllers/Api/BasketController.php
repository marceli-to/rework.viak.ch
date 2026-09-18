<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Bookings\CompleteCheckout;
use App\Actions\Bookings\PriceBasket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\CompleteCheckoutRequest;
use App\Http\Requests\Bookings\PriceBasketRequest;
use App\Http\Resources\BasketResource;
use App\Http\Resources\CheckoutResource;
use Illuminate\Http\JsonResponse;

/**
 * Pricing a basket, and turning one into bookings ([[06-bookings]]).
 *
 * The basket itself is **not** stored server-side. Legacy kept it in the session
 * through `BasketStore`, which is why it evaporated at checkout and left nothing
 * recording that two bookings had been one purchase. Here the client holds the
 * selection, the server prices it on every request, and the thing that persists
 * is the [[Checkout]] — which exists because a discount has to be level with
 * something.
 */
class BasketController extends Controller
{
	/** Prices the current selection. Called on every change to the basket. */
	public function price(PriceBasketRequest $request, PriceBasket $price): BasketResource
	{
		return new BasketResource(
			$price->execute($request->selections(), $request->code(), $request->user())
		);
	}

	/**
	 * Confirms it.
	 *
	 * The basket is priced again here, from scratch, and the total the client
	 * says it displayed is compared against the result. A checkout whose price
	 * has moved is refused rather than quietly completed at the new number.
	 */
	public function store(
		CompleteCheckoutRequest $request,
		PriceBasket $price,
		CompleteCheckout $checkout,
	): JsonResponse {
		$basket = $price->execute($request->selections(), $request->code(), $request->user());

		$completed = $checkout->execute(
			user: $request->user(),
			basket: $basket,
			totalShown: $request->totalShown(),
			invoiceAddress: $request->invoiceAddress(),
		);

		return (new CheckoutResource($completed->load(['bookings.event.course', 'discountCode'])))
			->response()
			->setStatusCode(201);
	}
}
