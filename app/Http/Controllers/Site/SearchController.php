<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Search\SearchIndex;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * `/de/suche?q=…`, where the header's field goes on Enter ([[15-search]]):
 * every hit as one list, in the order the index ranked them, each saying
 * what it is with its badge (Marcel, 2026-10-26, rather than groups).
 */
class SearchController extends Controller
{
	public function __invoke(Request $request, SearchIndex $index): View
	{
		$query = trim($request->string('q')->limit(100, '')->value());

		return view('site.search.index', [
			'query' => $query,
			'hits' => mb_strlen($query) < 2 ? collect() : $index->search($query, app()->getLocale(), 60),
		]);
	}
}
