<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\LicenceProduct;
use App\Models\Software;
use App\Support\LicenceFilter;
use App\Support\SiteUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * **An Angebot's licence page** (#71, the client 2026-11-12): every licence
 * of the Angebot on one page, product by product, each licence a block of
 * its own with its *Anzahl* and button; *Systemvoraussetzungen* once. Where
 * the licences are bought ([[14-angebote]], the client 2026-10-29: the
 * Angebot page is the overview). Drawn as a course page is.
 *
 * Until 2026-11-12 one page per product (Marcel, 2026-10-29); a product's
 * old URL now leads to its block. A product is on the site when it is
 * listed (published, a licence listed) under a published software.
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
			->with(['categories', 'manufacturer', 'variants', 'software.media', 'software.hosts'])
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

	/**
	 * The Angebot by its slug; failing that, a product's page of before
	 * #71, sent to its block on its Angebot's page (one hop, 301). An
	 * Angebot wins a slug both have: V-Ray's product is V-Ray's block anyway.
	 */
	public function show(string $slug): View|RedirectResponse
	{
		$locale = app()->getLocale();

		$software = Software::query()->sellsLicences()
			->where('slug->'.$locale, $slug)
			->with(['media', 'products' => fn ($query) => $query->listed()->ordered()->with(['manufacturer', 'variants', 'hosts'])])
			->first();

		if ($software === null) {
			$product = $this->onSite()->where('slug->'.$locale, $slug)->with('software')->firstOrFail();

			return redirect(SiteUrl::licence($product), 301);
		}

		return view('site.licences.show', ['software' => $software]);
	}

	/** @return Builder<LicenceProduct> listed, under a published software */
	private function onSite(): Builder
	{
		return LicenceProduct::query()
			->listed()
			->whereHas('software', fn (Builder $software) => $software->published());
	}
}
