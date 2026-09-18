<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\StoreAddressRequest;
use App\Http\Resources\UserAddressResource;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Invoice addresses — where the bill goes when it is not the student's own
 * ([[08-accounts]]).
 *
 * 126 of 710 bookings use one, and 107 users hold the 125 rows.
 */
class AddressController extends Controller
{
	public function index(Request $request): AnonymousResourceCollection
	{
		return UserAddressResource::collection($request->user()->addresses()->get());
	}

	public function store(StoreAddressRequest $request): JsonResponse
	{
		$address = $request->user()->addresses()->create($request->validated());

		return (new UserAddressResource($address))->response()->setStatusCode(201);
	}

	public function update(StoreAddressRequest $request, UserAddress $address): UserAddressResource
	{
		$address->update($request->validated());

		return new UserAddressResource($address);
	}

	public function destroy(UserAddress $address): JsonResponse
	{
		$this->authorize('delete', $address);

		// Soft-deleted, because bookings froze a *copy* of the address at
		// checkout rather than pointing at this row — but an invoice that has
		// gone out should still be traceable to where it was sent.
		$address->delete();

		return response()->json(status: 204);
	}
}
