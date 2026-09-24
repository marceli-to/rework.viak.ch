<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
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

	private function loadForShow(Course $course): Course
	{
		return $course->load([
			'categories', 'levels', 'languages', 'software', 'tags',
			'events' => fn ($query) => $query->active()->upcoming()->with(['dates', 'location']),
		]);
	}
}
