<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTestimonialRequest;
use App\Http\Resources\Admin\TestimonialFormResource;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * *Seiteninhalte → Testimonials* ([[07-dashboard]]). A handful of rows, so the
 * list is whole. It is not reordered: each page orders its own testimonials
 * in the picker.
 */
class TestimonialController extends Controller
{
	public function index(): AnonymousResourceCollection
	{
		return TestimonialFormResource::collection(Testimonial::query()->with(['courses', 'software', 'subject'])->ordered()->get());
	}

	public function show(Testimonial $testimonial): TestimonialFormResource
	{
		return new TestimonialFormResource($testimonial->load(['courses', 'software', 'subject']));
	}

	/** A new one goes to the end of the list. */
	public function store(SaveTestimonialRequest $request): JsonResponse
	{
		$testimonial = Testimonial::create([
			...$request->testimonialAttributes(),
			'order' => (int) Testimonial::max('order') + 1,
		]);

		return (new TestimonialFormResource($testimonial))->response()->setStatusCode(201);
	}

	public function update(SaveTestimonialRequest $request, Testimonial $testimonial): TestimonialFormResource
	{
		$testimonial->update($request->testimonialAttributes());

		return new TestimonialFormResource($testimonial->load(['courses', 'software', 'subject']));
	}

	/** Its placements go with it — the foreign key cascades. */
	public function destroy(Testimonial $testimonial): JsonResponse
	{
		$testimonial->delete();

		return response()->json(status: 204);
	}
}
