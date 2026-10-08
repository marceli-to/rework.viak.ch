<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveLicenceProductRequest;
use App\Http\Resources\Admin\LicenceProductFormResource;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * *Lizenzen* ([[05-licences]]): the catalogue, which the client keeps
 * themselves. About forty products, so the list is whole, grouped by
 * software on the screen.
 *
 * The slug is made from the title once, when the product is created, as a
 * Vorhaben's is. Variants are matched by uuid: one left out of the form is
 * deleted (softly, an order may point at it), one without a uuid is new.
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
		$product = DB::transaction(function () use ($request): LicenceProduct {
			$attributes = $request->productAttributes();

			$product = LicenceProduct::create([
				...$attributes,
				'slug' => LicenceProduct::freeSlug($attributes['title']['de']),
				'order' => (int) LicenceProduct::query()->where('software_id', $attributes['software_id'])->max('order') + 1,
			]);

			$this->syncVariants($product, $request->variants());

			return $product;
		});

		return (new LicenceProductFormResource($this->loaded($product)))->response()->setStatusCode(201);
	}

	public function update(SaveLicenceProductRequest $request, LicenceProduct $product): LicenceProductFormResource
	{
		DB::transaction(function () use ($request, $product): void {
			$product->update($request->productAttributes());
			$this->syncVariants($product, $request->variants());
		});

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

	/** @param  array<int, array<string, mixed>>  $rows */
	private function syncVariants(LicenceProduct $product, array $rows): void
	{
		$existing = $product->variants()->get()->keyBy('uuid');
		$kept = [];

		foreach ($rows as $row) {
			$uuid = $row['uuid'];
			unset($row['uuid']);

			if ($uuid !== null && $existing->has($uuid)) {
				$existing[$uuid]->update($row);
				$kept[] = $uuid;
			} else {
				$product->variants()->create($row);
			}
		}

		// Not `except()`: on an Eloquent collection it compares primary keys, not uuids.
		$existing->reject(fn (LicenceVariant $variant) => in_array($variant->uuid, $kept, true))->each->delete();
	}

	private function loaded(LicenceProduct $product): LicenceProduct
	{
		return $product->refresh()->load(['software', 'manufacturer', 'variants']);
	}
}
