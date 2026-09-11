<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Courses\CreateCourse;
use App\Actions\Courses\UpdateCourse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\StoreCourseRequest;
use App\Http\Requests\Courses\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Legacy `Api/Dashboard/CourseController` was 148 LOC of query building,
 * taxonomy syncing and response shaping. Each method here does three things:
 * take a FormRequest, call an Action, return a Resource.
 */
class CourseController extends Controller
{
	public function index(Request $request): AnonymousResourceCollection
	{
		$courses = Course::query()
			->when(! $request->user()?->isAdmin(), fn ($query) => $query->published())
			->with(['categories', 'levels', 'software'])
			->withCount('events')
			->ordered()
			->get();

		return CourseResource::collection($courses);
	}

	public function show(Course $course): CourseResource
	{
		$this->authorize('view', $course);

		return new CourseResource($this->loadForShow($course));
	}

	public function store(StoreCourseRequest $request, CreateCourse $create): JsonResponse
	{
		$course = $create->execute($request->courseAttributes());
		$this->syncTaxonomies($course, $request->taxonomies());

		return (new CourseResource($this->loadForShow($course)))
			->response()
			->setStatusCode(201);
	}

	public function update(UpdateCourseRequest $request, Course $course, UpdateCourse $update): CourseResource
	{
		$course = $update->execute($course, $request->courseAttributes());
		$this->syncTaxonomies($course, $request->taxonomies());

		return new CourseResource($this->loadForShow($course));
	}

	public function destroy(Course $course): JsonResponse
	{
		$this->authorize('delete', $course);

		$course->delete();

		return response()->json(status: 204);
	}

	private function loadForShow(Course $course): Course
	{
		return $course->load([
			'categories', 'levels', 'languages', 'software', 'tags',
			'events' => fn ($query) => $query->active()->upcoming()->with(['dates', 'location']),
		]);
	}

	/** @param array<string, array<int, int>> $taxonomies */
	private function syncTaxonomies(Course $course, array $taxonomies): void
	{
		foreach ($taxonomies as $relation => $ids) {
			$course->{$relation}()->sync($ids);
		}
	}
}
