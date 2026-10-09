<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Licences\PlaceLicenceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLicenceOrderRequest;
use App\Http\Resources\Admin\LicenceOrderResource;
use App\Http\Resources\Admin\LicenceOrderRowResource;
use App\Models\LicenceOrder;
use App\Models\LicenceOrderItem;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * *Bestellungen* ([[05-licences]]): the licence orders, and **the dispatch
 * worklist** that is this chunk's real deliverable. Fulfilment is a person at
 * VIAK ordering from the reseller and forwarding the licence, so an order stays
 * *offen* until each of its lines is marked sent.
 *
 * An order taken by mail or phone is entered from here, for one customer:
 * any variant, the hidden ones too (#36), or a free line.
 */
class LicenceOrderController extends Controller
{
	private const PER_PAGE = 50;

	/** `offen`: a line still to send, oldest first, as a queue is worked. `versendet`: newest first. */
	public function index(Request $request): AnonymousResourceCollection
	{
		$status = (string) $request->query('status');
		abort_unless(in_array($status, ['offen', 'versendet'], true), 404);

		$orders = LicenceOrder::query()
			->when($status === 'offen', fn (Builder $query) => $query->outstanding()->orderBy('created_at')->orderBy('id'))
			->when($status === 'versendet', fn (Builder $query) => $query->dispatched()->orderByDesc('created_at')->orderByDesc('id'))
			->with(['user', 'invoice', 'items'])
			->tap(fn (Builder $query) => $this->search($query, (string) $request->query('suche', '')))
			->paginate(self::PER_PAGE);

		return LicenceOrderRowResource::collection($orders);
	}

	public function show(LicenceOrder $order): LicenceOrderResource
	{
		return new LicenceOrderResource($order->load(['user', 'invoice.document', 'items.dispatcher', 'enteredBy']));
	}

	/**
	 * What the entry form needs for one customer: who, where the invoice may
	 * go, and the whole catalogue, **unlisted variants included** and marked.
	 */
	public function create(User $customer): JsonResponse
	{
		$products = LicenceProduct::query()
			->with(['software', 'variants'])
			->orderBy('order')->orderBy('id')
			->get()
			->filter(fn (LicenceProduct $product) => $product->variants->isNotEmpty())
			->sortBy(fn (LicenceProduct $product) => mb_strtolower($product->getTranslation('title', 'de')))
			->values();

		return response()->json(['data' => [
			'customer' => [
				'uuid' => $customer->uuid,
				'name' => $customer->name,
				'email' => $customer->email,
				'address' => implode(', ', array_filter([$customer->company, $customer->name, $customer->city], 'filled')),
			],
			'addresses' => $customer->addresses()->get()->map(fn (UserAddress $address) => [
				'uuid' => $address->uuid,
				'label' => $address->summary(),
			]),
			// For the form's running total; the invoice computes its own, per line ([[Vat]]).
			'vat_rate' => (string) config('invoice.vat_rate'),
			'vat_label' => config('invoice.vat_label'),
			'products' => $products->map(fn (LicenceProduct $product) => [
				'title' => $product->getTranslation('title', 'de'),
				'hosts' => $product->hosts ?? [],
				'variants' => $product->variants->map(fn (LicenceVariant $variant) => [
					'uuid' => $variant->uuid,
					'label' => $variant->shopLabel(),
					'sku' => $variant->sku,
					'price' => (string) $variant->price,
					'min_quantity' => $variant->min_quantity,
					'listed' => $variant->listed,
				]),
			]),
		]]);
	}

	public function store(StoreLicenceOrderRequest $request, User $customer, PlaceLicenceOrder $place): JsonResponse
	{
		abort_if($customer->deactivated_at !== null, 422, 'Dieses Konto ist deaktiviert.');

		$order = $place->execute(
			user: $customer,
			lines: $request->lines(),
			invoiceAddress: $request->invoiceAddress(),
			deliveryEmail: $request->validated('delivery_email'),
			enteredBy: $request->user(),
		);

		return response()->json(['data' => ['uuid' => $order->uuid]], 201);
	}

	/** *Versendet*, per line, and back: a tick set by mistake is taken off again. */
	public function dispatch(Request $request, LicenceOrderItem $item): JsonResponse
	{
		$dispatched = $request->validate(['dispatched' => ['required', 'boolean']])['dispatched'];

		$item->forceFill($dispatched
			? ['dispatched_at' => $item->dispatched_at ?? now(), 'dispatched_by' => $item->dispatched_by ?? $request->user()->id]
			: ['dispatched_at' => null, 'dispatched_by' => null])->save();

		return response()->json(['data' => (new LicenceOrderResource($item->order->load(['user', 'invoice.document', 'items.dispatcher', 'enteredBy'])))->resolve()]);
	}

	/** Every word has to match the number, the customer, or a line's title or article number. */
	private function search(Builder $query, string $search): void
	{
		foreach (preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $word) {
			$like = '%'.addcslashes($word, '%_\\').'%';

			$query->where(fn (Builder $query) => $query
				->where('number', 'like', $like)
				->orWhereHas('user', fn (Builder $user) => $user
					->where('first_name', 'like', $like)
					->orWhere('last_name', 'like', $like)
					->orWhere('city', 'like', $like)
					->orWhere('company', 'like', $like)
					->orWhere('email', 'like', $like))
				->orWhereHas('items', fn (Builder $items) => $items
					->where('title', 'like', $like)
					->orWhere('sku', 'like', $like)));
		}
	}
}
