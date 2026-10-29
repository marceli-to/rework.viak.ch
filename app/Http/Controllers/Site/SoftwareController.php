<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Software;
use App\Support\Recommendations;
use App\Support\SiteUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * One Angebot's page ([[14-angebote]]): a software with its courses and
 * licences, `/de/angebote/{slug}`. The software list it once had became the
 * licence list ([[LicenceController::index]]) on 2026-10-29, when the shop
 * moved to the product level. An Angebot is a software with a listed licence
 * or a published course ([[Software::scopeOnSite]]).
 */
class SoftwareController extends Controller
{
	/** The prototype's `/de/software/{slug}`, 301 to the Angebot's URL. */
	public function redirectLegacy(string $slug): RedirectResponse
	{
		return redirect(SiteUrl::software($slug), 301);
	}

	public function show(string $slug): View
	{
		$software = $this->onSite()
			->where('slug->'.app()->getLocale(), $slug)
			->with(['categories', 'media', 'projects', 'products' => $this->products()])
			->firstOrFail();

		// A row each, with the dates ahead it reads its next one from ([[14-angebote]]).
		$courses = $software->courses()
			->published()
			->with(['events' => fn ($query) => $query->published()->active()->upcoming()->with(['dates', 'location'])])
			->ordered()
			->get();

		return view('site.software.show', [
			'software' => $software,
			'products' => $software->products->filter->isListed()->values(),
			'courses' => $courses,
			// *Das könnte dich auch interessieren*, without the courses the
			// page already lists under *Kurse* ([[Recommendations]]).
			'recommended' => Recommendations::for([$software], $courses->modelKeys(), [$software->id]),
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
		return fn ($query) => $query->published()->ordered()->with(['manufacturer', 'variants', 'hosts']);
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
