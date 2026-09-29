<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveDiscountCodeRequest;
use App\Http\Resources\Admin\DiscountCodeFormResource;
use App\Models\DiscountCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * *Rabatt-Codes* ([[07-dashboard]], step 6) — legacy's
 * `Dashboard/DiscountCodeController`, which chunk 06's model had no endpoints for.
 *
 * About a hundred codes, so the list is whole and splits and searches in the
 * browser, as legacy's does.
 */
class DiscountCodeController extends Controller
{
	/** Newest first: the code just made is the one being looked for. */
	public function index(): AnonymousResourceCollection
	{
		return DiscountCodeFormResource::collection(DiscountCode::query()->withUsage()->latest('id')->get());
	}

	public function show(DiscountCode $discountCode): DiscountCodeFormResource
	{
		return new DiscountCodeFormResource($discountCode);
	}

	public function store(SaveDiscountCodeRequest $request): JsonResponse
	{
		return (new DiscountCodeFormResource(DiscountCode::create($request->codeAttributes())))->response()->setStatusCode(201);
	}

	/**
	 * A used code can still change: every booking keeps the amount it was
	 * given (`bookings.discount_amount`), so no past order moves.
	 */
	public function update(SaveDiscountCodeRequest $request, DiscountCode $discountCode): DiscountCodeFormResource
	{
		$discountCode->update($request->codeAttributes());

		return new DiscountCodeFormResource($discountCode);
	}

	/**
	 * Soft-deleted, as legacy did: bookings and checkouts that used it keep
	 * pointing at it, and it stops being redeemable ([[DiscountCode::isRedeemableOn]]).
	 */
	public function destroy(DiscountCode $discountCode): JsonResponse
	{
		$discountCode->delete();

		return response()->json(status: 204);
	}
}
