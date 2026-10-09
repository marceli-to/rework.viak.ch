<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveLicenceProductRequest;
use App\Http\Resources\Admin\LicenceProductFormResource;
use App\Models\LicenceProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * *Lizenzen* ([[05-licences]]): the catalogue, which the client keeps
 * themselves. About forty products, so the list is whole, grouped by
 * software on the screen.
 *
 * The slug is made from the title once, when the product is created, as a
 * Vorhaben's is. Variants are saved on their own ([[LicenceVariantController]]).
 */
class LicenceProductController extends Controller
{
	public function index(): AnonymousResourceCollection
	{
		return LicenceProductFormResource::collection(
			LicenceProduct::query()->with(['software', 'manufacturer', 'variants'])->ordered()->get()
		);
	}

	public function show(LicenceProduct $product): LicenceProductFormResource
	{
		return new LicenceProductFormResource($this->loaded($product));
	}

	/** A new one goes to the end of its group. */
	public function store(SaveLicenceProductRequest $request): JsonResponse
	{
		$attributes = $request->productAttributes();

		$product = LicenceProduct::create([
			...$attributes,
			'slug' => LicenceProduct::freeSlug($attributes['title']['de']),
			'order' => (int) LicenceProduct::query()->where('software_id', $attributes['software_id'])->max('order') + 1,
		]);
		$product->hosts()->sync($request->hostIds());

		return (new LicenceProductFormResource($this->loaded($product)))->response()->setStatusCode(201);
	}

	public function update(SaveLicenceProductRequest $request, LicenceProduct $product): LicenceProductFormResource
	{
		$product->update($request->productAttributes());
		$product->hosts()->sync($request->hostIds());

		return new LicenceProductFormResource($this->loaded($product));
	}

	public function destroy(LicenceProduct $product): JsonResponse
	{
		DB::transaction(function () use ($product): void {
			$product->variants()->delete();
			$product->delete();
		});

		return response()->json(status: 204);
	}

	private function loaded(LicenceProduct $product): LicenceProduct
	{
		return $product->refresh()->load(['software', 'manufacturer', 'variants', 'hosts']);
	}
}
