<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Models\Course;
use App\Models\LicenceProduct;
use App\Models\Software;
use Illuminate\Support\Collection;

/**
 * **What a hit says besides its lead** (#57, Oliver 2026-11-17: "as much as
 * possible", [[15-search]]): a course's next date and fee, an Angebot's *Kurs
 * ab* and *Lizenz ab*, a licence's *ab CHF*. Worked out as the hits are
 * shown, not stored in the index, because dates pass and prices change
 * without the record being saved. One query per kind for the list.
 *
 * The words are the tiles': a course as its card (*ab 12. Januar 2027*, the
 * course fee, none for a free date), an Angebot as its tile ([[Software::courseFrom]],
 * [[Software::licenceFrom]]). Prices net, as everywhere on the site.
 */
final class SearchFacts
{
	/**
	 * The hits as the result list and the API hand them out, each with its
	 * `facts`, a list of short lines (possibly empty).
	 *
	 * @param  Collection<int, object>  $hits  from [[SearchIndex::search]]
	 * @return Collection<int, object{type: string, label: string, title: string, lead: ?string, url: string, facts: list<string>}>
	 */
	public static function attach(Collection $hits): Collection
	{
		$ids = fn (string $type) => $hits->where('type', $type)->pluck('searchable_id')->all();

		$courses = Course::query()->whereKey($ids('course'))
			->with(['events' => fn ($events) => $events->published()->active()->upcoming()])
			->get()->keyBy('id');
		$software = Software::query()->whereKey($ids('software'))
			->with(['products' => fn ($products) => $products->with('variants')])
			->get()->keyBy('id');
		$licences = LicenceProduct::query()->whereKey($ids('licence'))->with('variants')->get()->keyBy('id');

		return $hits->map(fn (object $hit) => (object) [
			'type' => $hit->type,
			'label' => $hit->label,
			'title' => $hit->title,
			'lead' => $hit->lead,
			'url' => $hit->url,
			'facts' => match ($hit->type) {
				'course' => self::course($courses->get($hit->searchable_id)),
				'software' => self::software($software->get($hit->searchable_id)),
				'licence' => self::licence($licences->get($hit->searchable_id)),
				default => [],
			},
		])->values();
	}

	/** @return list<string> */
	private static function course(?Course $course): array
	{
		if ($course === null) {
			return [];
		}

		$next = $course->events->first();

		return array_values(array_filter([
			$next ? 'ab '.$next->date->translatedFormat('j. F Y') : 'Daten auf Anfrage',
			$next?->free_of_charge || ! is_numeric($course->fee) || (float) $course->fee <= 0 ? null : self::chf($course->fee),
		]));
	}

	/** @return list<string> */
	private static function software(?Software $software): array
	{
		if ($software === null) {
			return [];
		}

		$course = $software->courseFrom();
		$licence = $software->licenceFrom();

		return array_values(array_filter([
			$course ? 'Kurs ab '.self::chf($course['price']).($course['dated'] ? '' : ', Daten auf Anfrage') : null,
			$licence ? 'Lizenz ab '.self::chf($licence) : null,
		]));
	}

	/** @return list<string> */
	private static function licence(?LicenceProduct $product): array
	{
		$from = $product?->fromPrice();

		return $from ? ['ab '.self::chf($from)] : [];
	}

	private static function chf(string|float|int $amount): string
	{
		return 'CHF '.number_format((float) $amount, 2, '.', "'");
	}
}
