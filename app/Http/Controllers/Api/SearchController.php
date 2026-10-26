<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Search\SearchIndex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The site search, as JSON ([[15-search]]): what the header field's
 * suggestions and the result page will draw. Public, as the pages it finds
 * are. Two characters at least; fewer answers an empty list, not an error,
 * because a field asks as it is typed into.
 */
class SearchController extends Controller
{
	public function __invoke(Request $request, SearchIndex $index): JsonResponse
	{
		$query = trim($request->string('q')->limit(100, '')->value());
		$limit = min(max($request->integer('limit', 8), 1), 30);

		$hits = mb_strlen($query) < 2 ? collect() : $index->search($query, app()->getLocale(), $limit);

		return response()->json(['data' => $hits->values()]);
	}
}
