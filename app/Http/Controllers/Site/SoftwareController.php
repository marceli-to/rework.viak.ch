<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Software;
use App\Support\SoftwareFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

/**
 * The software pages, the shop ([[05-licences]]): drawn as the course pages
 * are, the list with its filter and one page per software (Marcel,
 * 2026-10-08). Only a software with something to order is shown
 * ([[Software::scopeOnSite]]).
 */
class SoftwareController extends Controller
{
	/** The whole list rendered, the query string deciding what is hidden, as the course list does ([[CourseController]]). */
	public function index(): View
	{
		$software = $this->listed()->load(['categories', 'media']);

		return view('site.software.index', [
			'software' => $software,
			'filter' => SoftwareFilter::for($software, request()->query()),
		]);
	}

	public function show(string $slug): View
	{
		$software = $this->onSite()
			->where('slug->'.app()->getLocale(), $slug)
			->with(['categories', 'media', 'products' => $this->products()])
			->firstOrFail();

		return view('site.software.show', [
			'software' => $software,
			'products' => $software->products->filter->isListed()->values(),
			// As the course list's cards have them ([[CourseController::index]]).
			'courses' => $software->courses()
				->published()
				->with(['categories', 'media', 'events' => fn ($query) => $query->published()->active()->upcoming()->with('experts')])
				->ordered()
				->get(),
			'testimonials' => $software->testimonialsAbout()->published()->ordered()->get(),
			'browse' => $this->browse($software),
		]);
	}

	/**
	 * The list's order: by name, as the dashboard groups them.
	 *
	 * @return Collection<int, Software>
	 */
	private function listed(): Collection
	{
		return $this->onSite()
			->with(['products' => $this->products()])
			->get()
			->sortBy(fn (Software $software) => $software->getTranslation('title', app()->getLocale()), SORT_NATURAL | SORT_FLAG_CASE)
			->values();
	}

	/** @return Builder<Software> */
	private function onSite(): Builder
	{
		return Software::query()->onSite();
	}

	/** The published products, with what their row and the filter read. */
	private function products(): \Closure
	{
		return fn ($query) => $query->published()->ordered()->with(['manufacturer', 'variants']);
	}

	/**
	 * The previous and next software on the list, wrapping at the ends, as a
	 * course's pair does ([[CourseController::browse]]).
	 *
	 * @return array{prev: Software, next: Software}|null
	 */
	private function browse(Software $software): ?array
	{
		$list = $this->listed();

		if ($list->count() <= 1) {
			return null;
		}

		$at = $list->search(fn (Software $other) => $other->is($software));

		if ($at === false) {
			return null;
		}

		return [
			'prev' => $list[$at - 1] ?? $list->last(),
			'next' => $list[$at + 1] ?? $list->first(),
		];
	}
}
