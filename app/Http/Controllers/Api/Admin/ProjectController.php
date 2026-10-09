<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProjectRequest;
use App\Http\Resources\Admin\ProjectFormResource;
use App\Models\Course;
use App\Models\Project;
use App\Models\Software;
use App\Support\Slug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * *Seiteninhalte → Vorhaben* ([[04-content]]). A handful of rows, so the list
 * is whole, and **dragged into the order the homepage's tiles show**.
 *
 * The slug is made from the title once, when the Vorhaben is created, and not
 * again: a renamed Vorhaben keeps its URL, as a renamed course does
 * ([[UpdateCourse]]).
 */
class ProjectController extends Controller
{
	public function index(): AnonymousResourceCollection
	{
		return ProjectFormResource::collection(Project::query()->with(['courses:id,uuid', 'software:id,uuid'])->ordered()->get());
	}

	public function show(Project $project): ProjectFormResource
	{
		return new ProjectFormResource($project->load(['courses:id,uuid', 'software:id,uuid']));
	}

	/** A new one goes to the end of the list. */
	public function store(SaveProjectRequest $request): JsonResponse
	{
		$project = DB::transaction(function () use ($request): Project {
			$attributes = $request->projectAttributes();

			$project = Project::create([
				...$attributes,
				'slug' => $this->freeSlug($attributes['title']['de']),
				'order' => (int) Project::max('order') + 1,
			]);

			$this->syncCourses($project, $request->courses());
			$this->syncSoftware($project, $request->software());

			return $project;
		});

		return (new ProjectFormResource($project->load(['courses:id,uuid', 'software:id,uuid'])))->response()->setStatusCode(201);
	}

	public function update(SaveProjectRequest $request, Project $project): ProjectFormResource
	{
		DB::transaction(function () use ($request, $project): void {
			$project->update($request->projectAttributes());
			$this->syncCourses($project, $request->courses());
			$this->syncSoftware($project, $request->software());
		});

		return new ProjectFormResource($project->load(['courses:id,uuid', 'software:id,uuid']));
	}

	public function order(Request $request): JsonResponse
	{
		$uuids = $request->validate([
			'projects' => ['required', 'array'],
			'projects.*' => ['string', Rule::exists('projects', 'uuid')],
		])['projects'];

		DB::transaction(function () use ($uuids): void {
			foreach (array_values($uuids) as $position => $uuid) {
				Project::query()->where('uuid', $uuid)->update(['order' => $position + 1]);
			}
		});

		return response()->json(status: 204);
	}

	public function destroy(Project $project): JsonResponse
	{
		$project->delete();

		return response()->json(status: 204);
	}

	/** @param  array<int, string>  $uuids  in the order the page lists them */
	private function syncCourses(Project $project, array $uuids): void
	{
		$ids = Course::query()->whereIn('uuid', $uuids)->pluck('id', 'uuid');

		$project->courses()->sync(collect($uuids)
			->values()
			->mapWithKeys(fn (string $uuid, int $position) => [$ids[$uuid] => ['order' => $position + 1]])
			->all());
	}

	/** @param  array<int, string>  $uuids  in the order the page lists them */
	private function syncSoftware(Project $project, array $uuids): void
	{
		$ids = Software::query()->whereIn('uuid', $uuids)->pluck('id', 'uuid');

		$project->software()->sync(collect($uuids)
			->values()
			->mapWithKeys(fn (string $uuid, int $position) => [$ids[$uuid] => ['order' => $position + 1]])
			->all());
	}

	/**
	 * The title's slug, numbered if another Vorhaben has it already.
	 *
	 * @return array<string, string>
	 */
	private function freeSlug(string $title): array
	{
		$base = Slug::forTitles($title);

		for ($n = 1; ; $n++) {
			$slug = array_map(fn (string $slug) => $n === 1 ? $slug : "{$slug}-{$n}", $base);

			if (! Project::query()->where('slug->de', $slug['de'])->exists()) {
				return $slug;
			}
		}
	}
}
