<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * A fixed page's testimonials ([[Page]], [[07-dashboard]]): which ones stand
 * on it and in what order, picked in the dashboard's testimonial picker. The
 * picker sends the whole list each time, so a save is a sync.
 */
class PageTestimonialController extends Controller
{
	public function show(string $page): JsonResponse
	{
		$page = Page::for($page);

		return response()->json(['data' => [
			'label' => $page->label(),
			'picked' => $page->testimonials()->pluck('testimonials.uuid'),
			'testimonials' => Testimonial::query()->with('subject')->ordered()->get()->map(fn (Testimonial $testimonial) => [
				'uuid' => $testimonial->uuid,
				'quote' => $testimonial->getTranslation('quote', 'de', false) ?: '',
				'name' => $testimonial->name,
				'context' => $testimonial->getTranslation('context', 'de', false) ?: '',
				'subject_label' => $testimonial->subjectLabel(),
				'publish' => $testimonial->publish,
			]),
		]]);
	}

	public function update(Request $request, string $page): JsonResponse
	{
		$page = Page::for($page);

		$uuids = $request->validate([
			'testimonials' => ['present', 'array'],
			'testimonials.*' => ['string', 'distinct', Rule::exists('testimonials', 'uuid')],
		])['testimonials'];

		$ids = Testimonial::query()->whereIn('uuid', $uuids)->pluck('id', 'uuid');

		DB::transaction(fn () => $page->testimonials()->sync(
			collect($uuids)->values()->mapWithKeys(fn (string $uuid, int $position) => [$ids[$uuid] => ['order' => $position + 1]])->all()
		));

		return response()->json(status: 204);
	}
}
