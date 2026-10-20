<?php

declare(strict_types=1);

namespace App\Support;

use App\Forms\Field;
use App\Models\Course;
use App\Models\Recommendation;
use App\Models\Software;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * *Das könnte dich auch interessieren* (client, 2026-10-20, `13-checkout.md`):
 * the courses and software VIAK picks for a software or a course, in its form,
 * shown under the software's page and in the basket. Customers put their own
 * bundle together from them, in place of the shop's fixed bundles. No price
 * rule: whether a bundle is cheaper is the client's open question.
 *
 * Picked as a Vorhaben's offers are: two ordered lists, courses then software
 * (`Field::offers`), kept in one `recommendations` table whatever the owner.
 */
final class Recommendations
{
	/** How many cards the basket shows at most. */
	public const BASKET = 6;

	/**
	 * The two pickers, for the software form and the course form alike: the
	 * Vorhaben's (`Field::offers`), courses then software.
	 *
	 * @return array<int, Field>
	 */
	public static function fields(): array
	{
		return [
			Field::offers('recommended_courses', fn () => Course::query()->orderBy('number')->get()
				->mapWithKeys(fn (Course $course) => [$course->uuid => [
					'label' => $course->getTranslation('title', 'de'),
					'hint' => $course->displayNumber(),
					'publish' => $course->publish,
				]])->all())->label('Empfohlene Kurse'),
			Field::offers('recommended_software', fn () => Software::query()->ordered()->get()
				->mapWithKeys(fn (Software $software) => [$software->uuid => [
					'label' => $software->getTranslation('title', 'de'),
					'publish' => $software->publish,
				]])->all())->label('Empfohlene Software'),
		];
	}

	/** The form's two lists. @return array{recommended_courses: array<int, string>, recommended_software: array<int, string>} */
	public static function form(Model $owner): array
	{
		return [
			'recommended_courses' => self::uuids($owner, Course::class),
			'recommended_software' => self::uuids($owner, Software::class),
		];
	}

	/** The owner's picks of one kind, as uuids in order: what the form holds. @return array<int, string> */
	public static function uuids(Model $owner, string $class): array
	{
		return Recommendation::query()
			->whereMorphedTo('owner', $owner)
			->where('item_type', (new $class)->getMorphClass())
			->orderBy('order')
			->get()
			->map(fn (Recommendation $row) => $class::query()->whereKey($row->item_id)->value('uuid'))
			->filter()
			->values()
			->all();
	}

	/**
	 * What the form sent, courses then software, each in its order.
	 *
	 * @param  array<int, string>  $courses
	 * @param  array<int, string>  $software
	 */
	public static function sync(Model $owner, array $courses, array $software): void
	{
		DB::transaction(function () use ($owner, $courses, $software): void {
			Recommendation::query()->whereMorphedTo('owner', $owner)->delete();

			$position = 0;
			foreach ([Course::class => $courses, Software::class => $software] as $class => $uuids) {
				$ids = $class::query()->whereIn('uuid', $uuids)->pluck('id', 'uuid');

				foreach ($uuids as $uuid) {
					if (! isset($ids[$uuid]) || ($owner instanceof $class && $owner->getKey() === $ids[$uuid])) {
						continue;
					}

					Recommendation::create([
						'owner_type' => $owner->getMorphClass(), 'owner_id' => $owner->getKey(),
						'item_type' => (new $class)->getMorphClass(), 'item_id' => $ids[$uuid],
						'order' => ++$position,
					]);
				}
			}
		});
	}

	/**
	 * The cards for one or more owners: the published courses and on-site
	 * software they recommend, in order, each once, leaving out what the
	 * customer already has (the basket's contents, or the page itself). Loaded
	 * as *Beliebte Angebote* loads its cards.
	 *
	 * @param  iterable<Model>  $owners
	 * @param  array<int, int>  $exceptCourses
	 * @param  array<int, int>  $exceptSoftware
	 * @return array{courses: Collection<int, Course>, software: Collection<int, Software>}
	 */
	public static function for(iterable $owners, array $exceptCourses = [], array $exceptSoftware = [], ?int $limit = null): array
	{
		$rows = collect($owners)
			->flatMap(fn (Model $owner) => Recommendation::query()->whereMorphedTo('owner', $owner)->orderBy('order')->get())
			->unique(fn (Recommendation $row) => $row->item_type.':'.$row->item_id);

		$ids = fn (string $class, array $except) => $rows
			->where('item_type', (new $class)->getMorphClass())
			->pluck('item_id')
			->reject(fn (int $id) => in_array($id, $except, true))
			->values();

		$courseIds = $ids(Course::class, $exceptCourses);
		$softwareIds = $ids(Software::class, $exceptSoftware);

		$courses = Course::query()->published()->whereKey($courseIds)
			->with(['categories', 'media', 'events' => fn ($query) => $query->published()->active()->upcoming()->with('experts')])
			->get()->sortBy(fn (Course $course) => $courseIds->search($course->id))->values();

		$software = Software::query()->onSite()->whereKey($softwareIds)
			->with(['categories', 'media', 'products' => fn ($query) => $query->published()->ordered()->with(['manufacturer', 'variants'])])
			->get()->sortBy(fn (Software $item) => $softwareIds->search($item->id))->values();

		if ($limit !== null) {
			$courses = $courses->take($limit)->values();
			$software = $software->take(max(0, $limit - $courses->count()))->values();
		}

		return ['courses' => $courses, 'software' => $software];
	}
}
