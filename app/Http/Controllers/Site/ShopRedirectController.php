<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Support\ShopRedirects;
use App\Support\SiteUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Every request on an old 3d-software.ch host, 301 in one hop to the page
 * that replaced it on the canonical host ([[ShopRedirects]]). The query
 * string is dropped: the old shop's were sessions and sorting, nothing the
 * new pages read.
 */
class ShopRedirectController
{
	public function __invoke(Request $request): RedirectResponse
	{
		return redirect()->away(SiteUrl::canonical(ShopRedirects::resolve($request->path())['target']), 301);
	}
}
