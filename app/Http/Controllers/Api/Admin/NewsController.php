<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Media\DeleteMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveNewsRequest;
use App\Http\Resources\Admin\NewsFormResource;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * *Seiteninhalte → News* ([[News]]): a short list, whole, **dragged into the
 * order the homepage shows**, as the team's is ([[TeamMemberController]]).
 */
class NewsController extends Controller
{
	public function index(): AnonymousResourceCollection
	{
		return NewsFormResource::collection(News::query()->ordered()->get());
	}

	public function show(News $news): NewsFormResource
	{
		return new NewsFormResource($news);
	}

	/** A new one goes to the top: the newest is the one VIAK wants seen. */
	public function store(SaveNewsRequest $request): JsonResponse
	{
		$news = DB::transaction(function () use ($request): News {
			News::query()->increment('order');

			return News::create([...$request->newsAttributes(), 'order' => 0]);
		});

		return (new NewsFormResource($news))->response()->setStatusCode(201);
	}

	public function update(SaveNewsRequest $request, News $news): NewsFormResource
	{
		$news->update($request->newsAttributes());

		return new NewsFormResource($news);
	}

	public function order(Request $request): JsonResponse
	{
		$uuids = $request->validate([
			'news' => ['required', 'array'],
			'news.*' => ['string', Rule::exists('news', 'uuid')],
		])['news'];

		DB::transaction(function () use ($uuids): void {
			foreach (array_values($uuids) as $position => $uuid) {
				News::query()->where('uuid', $uuid)->update(['order' => $position + 1]);
			}
		});

		return response()->json(status: 204);
	}

	/** The picture goes with it, file and all. */
	public function destroy(News $news, DeleteMedia $delete): JsonResponse
	{
		DB::transaction(function () use ($news, $delete): void {
			$news->media->each(fn ($media) => $delete->execute($media));
			$news->delete();
		});

		return response()->json(status: 204);
	}
}
