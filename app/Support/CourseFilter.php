<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Course;
use Illuminate\Support\Collection;

/**
 * The course list's filter ([[09-public-site]]).
 *
 * Legacy's `App\Services\CourseFilter` turned into `whereHas` clauses and ran
 * per click over the network. Here it decides two things instead: **which
 * attributes each course carries**, which go to the browser as `data-facets`,
 * and **which courses the query string selects**, which decides what is hidden
 * on the first paint. `course-filter.js` then applies the same rule to the same
 * uuids, so the server and the browser cannot drift apart.
 *
 * One value per attribute, as legacy has it. The seven and their meanings are
 * legacy's exactly, including the two that are not taxonomies: **location** is
 * the course's `online` flag rendered as `online`/`offline`, and **expert** is
 * whoever teaches one of its upcoming events.
 */
class CourseFilter
{
	/**
	 * The seven attributes, in the order the panel draws them.
	 *
	 * Category is drawn as a list of links and the other six as selects, which
	 * is presentation — every one of them behaves identically here.
	 */
	public const ATTRIBUTES = ['category', 'location', 'software', 'level', 'language', 'expert', 'tag'];

	/** @var array<string, array<string, string[]>>|null */
	private ?array $facets = null;

	/**
	 * @param  Collection<int, Course>  $courses
	 * @param  array<string, string|null>  $selected
	 */
	private function __construct(
		private readonly Collection $courses,
		private readonly array $selected,
	) {}

	/**
	 * @param  Collection<int, Course>  $courses
	 * @param  array<string, mixed>  $query
	 */
	public static function for(Collection $courses, array $query): self
	{
		$selected = [];

		foreach (self::ATTRIBUTES as $attribute) {
			$value = trim((string) ($query[$attribute] ?? ''));
			$selected[$attribute] = $value !== '' ? $value : null;
		}

		return new self($courses, $selected);
	}

	/** @return array<string, string|null> */
	public function selected(): array
	{
		return $this->selected;
	}

	/**
	 * The same selection as Alpine wants it: **`''` rather than `null`**, because
	 * six of the seven controls are selects and `''` is a select's own way of
	 * saying nothing is chosen. `course-filter.js` says more about why.
	 *
	 * @return array<string, string>
	 */
	public function seed(): array
	{
		return array_map(fn (?string $value) => $value ?? '', $this->selected);
	}

	public function any(): bool
	{
		return collect($this->selected)->contains(fn (?string $value) => $value !== null);
	}

	/**
	 * What each course can be filtered by, keyed by its uuid.
	 *
	 * @return array<string, array<string, string[]>>
	 */
	public function facets(): array
	{
		return $this->facets ??= $this->courses
			->mapWithKeys(fn (Course $course) => [$course->uuid => [
				'category' => $course->categories->pluck('uuid')->all(),
				// Not a taxonomy: legacy's Ort is the course's own flag, and its
				// two options are the only two values it can have.
				'location' => [$course->online ? 'online' : 'offline'],
				'software' => $course->software->pluck('uuid')->all(),
				'level' => $course->levels->pluck('uuid')->all(),
				'language' => $course->languages->pluck('uuid')->all(),
				// `events` is already narrowed to the published, upcoming ones,
				// which is the set legacy matches an expert against.
				'expert' => $course->events
					->flatMap(fn ($event) => $event->experts->pluck('uuid'))
					->unique()->values()->all(),
				'tag' => $course->tags->pluck('uuid')->all(),
			]])
			->all();
	}

	/**
	 * The uuids the query string selects — everything when it selects nothing.
	 *
	 * @return string[]
	 */
	public function matching(): array
	{
		$facets = $this->facets();

		return $this->courses
			->filter(fn (Course $course) => $this->matches($facets[$course->uuid]))
			->pluck('uuid')
			->all();
	}

	/** @param  array<string, string[]>  $facets */
	public function matches(array $facets): bool
	{
		foreach ($this->selected as $attribute => $value) {
			if ($value !== null && ! in_array($value, $facets[$attribute], true)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * What the panel offers, as value => label.
	 *
	 * **Only what is actually on the page.** Legacy reads the pivot tables, so
	 * it can offer a filter that no published course carries and that therefore
	 * returns nothing. Deriving from the rendered courses instead makes every
	 * option lead somewhere, and costs no query — the relations are loaded.
	 *
	 * Sorted by label, and `sortBy` compares byte-wise exactly as legacy's
	 * `sort()` does, which is why `pCon.planner` comes after `ZBrush`.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function options(): array
	{
		return [
			'category' => $this->labels($this->courses->flatMap->categories),
			'location' => ['online' => 'online', 'offline' => 'vor Ort'],
			'software' => $this->labels($this->courses->flatMap->software),
			'level' => $this->labels($this->courses->flatMap->levels),
			'language' => $this->labels($this->courses->flatMap->languages),
			'expert' => $this->courses
				->flatMap(fn (Course $course) => $course->events->flatMap->experts)
				->unique('id')
				->sortBy('first_name')
				->mapWithKeys(fn ($expert) => [$expert->uuid => $expert->name])
				->all(),
			'tag' => $this->labels($this->courses->flatMap->tags),
		];
	}

	/**
	 * @param  Collection<int, mixed>  $taxonomy
	 * @return array<string, string>
	 */
	private function labels(Collection $taxonomy): array
	{
		$locale = app()->getLocale();

		return $taxonomy
			->unique('id')
			->mapWithKeys(fn ($term) => [$term->uuid => $term->getTranslation('title', $locale)])
			->sort()
			->all();
	}
}
