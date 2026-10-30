<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Category;
use App\Models\Course;
use App\Models\Software;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Everything VIAK offers, one section per category ([[14-angebote]]): what
 * *Alle Angebote* draws ([[OfferController]]) and `/llms.txt` lists
 * ([[LlmsController]]).
 *
 * A tile is an **Angebot**, a software with its tag ([[Software::offer]]),
 * leading to its page; or a **course with no Angebot** (Visual Facilitator,
 * Urban Sketching), tagged *Kurs*, leading straight to the course page
 * (#50, 2026-10-29). Each sits under every category it has; what has none
 * is gathered under *Weitere*, so nothing drops off the page while VIAK
 * sorts it (#66).
 */
final class Angebote
{
	/** @return Collection<int, array{title: string, tiles: Collection<int, array{title: string, kind: string, href: string, categories: list<int>}>}> */
	public static function sections(): Collection
	{
		$tiles = self::angebote()->concat(self::courses())
			->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)
			->values();

		$categories = Category::query()->published()->ordered()->get();
		$known = $categories->modelKeys();

		return $categories
			->map(fn (Category $category) => [
				'title' => $category->getTranslation('title', app()->getLocale()),
				'tiles' => $tiles->filter(fn (array $tile) => in_array($category->id, $tile['categories'], true))->values(),
			])
			->push([
				'title' => 'Weitere',
				'tiles' => $tiles->filter(fn (array $tile) => array_intersect($tile['categories'], $known) === [])->values(),
			])
			->filter(fn (array $section) => $section['tiles']->isNotEmpty())
			->values()
			->toBase();
	}

	/** @return Collection<int, array{title: string, kind: string, href: string, categories: list<int>}> */
	private static function angebote(): Collection
	{
		$locale = app()->getLocale();

		return Software::query()->onSite()->withOffer()->with('categories')->get()
			->map(fn (Software $software) => [
				'title' => (string) $software->getTranslation('title', $locale),
				'kind' => (string) $software->offer(),
				'href' => SiteUrl::software($software->getTranslation('slug', $locale)),
				'categories' => $software->categories->modelKeys(),
			])
			->toBase();
	}

	/**
	 * The published courses no Angebot holds: their software, if any, is not
	 * on the site.
	 *
	 * @return Collection<int, array{title: string, kind: string, href: string, categories: list<int>}>
	 */
	private static function courses(): Collection
	{
		$locale = app()->getLocale();

		return Course::query()->published()
			->whereDoesntHave('software', fn (Builder $software) => $software->onSite())
			->with('categories')
			->get()
			->map(fn (Course $course) => [
				'title' => (string) $course->getTranslation('title', $locale),
				'kind' => 'Kurs',
				'href' => SiteUrl::course($course->getTranslation('slug', $locale)),
				'categories' => $course->categories->modelKeys(),
			])
			->toBase();
	}
}
