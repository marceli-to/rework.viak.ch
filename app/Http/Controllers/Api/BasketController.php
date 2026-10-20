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
use App\Models\Course;
use App\Models\Software;
use App\Support\Recommendations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

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
			$price->execute($request->selections(), $request->code(), $request->user(), $request->licences())
		);
	}

	/**
	 * *Das könnte dich auch interessieren* under the basket ([[Recommendations]]):
	 * what VIAK picked for the courses and the software in it, without what is
	 * already there, at most six cards. Rendered here, since the cards are
	 * Blade components and the basket page is drawn in the browser.
	 */
	public function recommendations(Request $request): JsonResponse
	{
		$input = $request->validate([
			'items' => ['array'],
			'items.*.event' => ['string'],
			'licences' => ['array'],
			'licences.*.variant' => ['string'],
		]);

		$courses = Course::query()->whereHas('events', fn ($query) => $query->whereIn('uuid', collect($input['items'] ?? [])->pluck('event')))->get();
		$software = Software::query()->whereHas('products.variants', fn ($query) => $query->whereIn('uuid', collect($input['licences'] ?? [])->pluck('variant')))->get();

		$recommended = Recommendations::for($courses->concat($software), $courses->modelKeys(), $software->modelKeys(), Recommendations::BASKET);

		$empty = $recommended['courses']->isEmpty() && $recommended['software']->isEmpty();

		return response()->json(['html' => $empty ? '' : Blade::render(
			'<x-recommendations :courses="$courses" :software="$software" />',
			['courses' => $recommended['courses'], 'software' => $recommended['software']],
		)]);
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
