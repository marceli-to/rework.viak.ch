<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\LicenceProduct;
use Illuminate\Database\Eloquent\Builder;
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
	public function show(string $slug): View
	{
		$product = LicenceProduct::query()
			->listed()
			->whereHas('software', fn (Builder $software) => $software->published())
			->where('slug->'.app()->getLocale(), $slug)
			->with(['software.media', 'manufacturer', 'variants', 'hosts'])
			->firstOrFail();

		return view('site.licences.show', [
			'product' => $product,
			'software' => $product->software,
		]);
	}
}
