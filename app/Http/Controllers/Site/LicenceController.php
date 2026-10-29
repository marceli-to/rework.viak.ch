<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\LicenceProduct;
use App\Support\LicenceFilter;
use App\Support\SiteUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One licence product, *Rhinoceros 8* with its six licences: where it is
 * bought ([[14-angebote]], the client 2026-10-29: buying only ever on the
 * product's own page, the Angebot is the overview). One page per product,
 * not per licence (Marcel, 2026-10-29). Drawn as a course page is.
 *
 * On the site when it is listed (published, a licence listed) under a
 * published software, which is then an Angebot by [[Software::scopeOnSite]].
 */
class LicenceController extends Controller
{
	/**
	 * *Software*, `/de/angebote/software`: every product on the site as a
	 * tile, A to Z, with the filter the software list had ([[LicenceFilter]]).
	 * The Software view of the catalogue is the product level (the client,
	 * 2026-10-29: *hier werden direkt die Produkte angezeigt*).
	 */
	public function index(): View
	{
		$products = $this->onSite()
			->with(['categories', 'manufacturer', 'variants', 'software.media'])
			->get()
			->sortBy(fn (LicenceProduct $product) => $product->getTranslation('title', app()->getLocale()), SORT_NATURAL | SORT_FLAG_CASE)
			->values();

		return view('site.licences.index', [
			'products' => $products,
			'filter' => LicenceFilter::for($products, request()->query()),
		]);
	}

	/** The prototype's `/de/software`, 301 to the list's place under Angebote, filter kept. */
	public function redirectList(Request $request): RedirectResponse
	{
		$query = $request->getQueryString();

		return redirect(SiteUrl::softwareIndex().($query ? "?{$query}" : ''), 301);
	}

	public function show(string $slug): View
	{
		$product = $this->onSite()
			->where('slug->'.app()->getLocale(), $slug)
			->with(['software.media', 'manufacturer', 'variants', 'hosts'])
			->firstOrFail();

		return view('site.licences.show', [
			'product' => $product,
			'software' => $product->software,
		]);
	}

	/** @return Builder<LicenceProduct> listed, under a published software */
	private function onSite(): Builder
	{
		return LicenceProduct::query()
			->listed()
			->whereHas('software', fn (Builder $software) => $software->published());
	}
}
