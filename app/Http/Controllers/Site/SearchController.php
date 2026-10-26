<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Search\SearchDocument;
use App\Support\Search\SearchIndex;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * `/de/suche?q=…`, where the header's field goes on Enter ([[15-search]]):
 * every hit, grouped by what it is, in the order [[SearchDocument::GROUPS]]
 * gives them, each group in the order the index ranked it.
 */
class SearchController extends Controller
{
	public function __invoke(Request $request, SearchIndex $index): View
	{
		$query = trim($request->string('q')->limit(100, '')->value());

		$hits = mb_strlen($query) < 2 ? collect() : $index->search($query, app()->getLocale(), 60);

		$groups = collect(SearchDocument::GROUPS)
			->map(fn (string $label, string $type) => ['label' => $label, 'hits' => $hits->where('type', $type)->values()])
			->filter(fn (array $group) => $group['hits']->isNotEmpty());

		return view('site.search.index', [
			'query' => $query,
			'count' => $hits->count(),
			'groups' => $groups,
		]);
	}
}
