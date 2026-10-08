<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveLicenceVariantRequest;
use App\Http\Resources\Admin\LicenceVariantFormResource;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * A product's variants ([[05-licences]]): listed on the product's form,
 * **dragged into the order its dropdown shows**, each saved in a form of its
 * own. Deleting is soft, since an order may point at the variant.
 */
class LicenceVariantController extends Controller
{
	public function index(LicenceProduct $product): AnonymousResourceCollection
	{
		return LicenceVariantFormResource::collection($product->variants()->with('product')->get());
	}

	public function show(LicenceVariant $variant): LicenceVariantFormResource
	{
		return new LicenceVariantFormResource($variant->load('product'));
	}

	/** A new one goes to the end of the dropdown. */
	public function store(SaveLicenceVariantRequest $request, LicenceProduct $product): JsonResponse
	{
		$variant = $product->variants()->create([
			...$request->variantAttributes(),
			'order' => (int) $product->variants()->max('order') + 1,
		]);

		return (new LicenceVariantFormResource($variant->load('product')))->response()->setStatusCode(201);
	}

	public function update(SaveLicenceVariantRequest $request, LicenceVariant $variant): LicenceVariantFormResource
	{
		$variant->update($request->variantAttributes());

		return new LicenceVariantFormResource($variant->load('product'));
	}

	public function order(Request $request, LicenceProduct $product): JsonResponse
	{
		$uuids = $request->validate([
			'variants' => ['required', 'array'],
			'variants.*' => ['string', Rule::exists('licence_variants', 'uuid')->where('licence_product_id', $product->id)],
		])['variants'];

		DB::transaction(function () use ($uuids): void {
			foreach (array_values($uuids) as $position => $uuid) {
				LicenceVariant::query()->where('uuid', $uuid)->update(['order' => $position + 1]);
			}
		});

		return response()->json(status: 204);
	}

	public function destroy(LicenceVariant $variant): JsonResponse
	{
		$variant->delete();

		return response()->json(status: 204);
	}
}
