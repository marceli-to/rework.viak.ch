<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Forms\LocationSchema;
use App\Forms\Schema;
use App\Forms\TermSchema;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Language;
use App\Models\Level;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Software;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * *Einstellungen* ([[07-dashboard]], step 6) — legacy's five settings lists,
 * which the course filter and the course pages are built on. Rarely edited,
 * so built cheaply: one screen, one form for the four terms ([[TermSchema]]),
 * one for places ([[LocationSchema]]), and this one controller.
 *
 * Since chunk 05 two more, after legacy's five: **Software**, the groups
 * courses and licence products hang off, and **Hersteller**. Both are a name
 * and nothing else until the software pages give a group copy; their badge
 * says how many licence products use them ([[05-licences]]).
 *
 * **Nothing in use is deleted.** Legacy deleted a category a course was filed
 * under; here the delete is refused while a course or a course date uses the
 * term, and says how many.
 */
class SettingController extends Controller
{
	/** @var array<string, class-string<Model>> */
	private const TERMS = [
		'categories' => Category::class,
		'languages' => Language::class,
		'levels' => Level::class,
		'tags' => Tag::class,
		'software' => Software::class,
		'manufacturers' => Manufacturer::class,
	];

	/** Every list, whole: 29 rows in all today. */
	public function index(): JsonResponse
	{
		$list = fn (string $class) => $class::query()->orderBy('order')->orderBy('id')->get()->map(fn (Model $term) => $this->term($term))->values();

		$lists = collect(self::TERMS)->except(['software', 'manufacturers'])->map($list);
		$lists['locations'] = Location::query()->orderBy('id')->get()->map(fn (Location $location) => $this->location($location))->values();
		$lists['software'] = $list(Software::class);
		$lists['manufacturers'] = $list(Manufacturer::class);

		return response()->json(['data' => $lists]);
	}

	public function show(string $kind, string $uuid): JsonResponse
	{
		return $this->answer($kind, $this->find($kind, $uuid));
	}

	/** A new term goes to the end of its list, published, as legacy's did. */
	public function store(Request $request, string $kind): JsonResponse
	{
		$data = $this->validated($request, $kind);
		$class = $this->model($kind);

		$record = $kind === 'locations'
			? Location::create($data)
			: $class::create([...$data, 'order' => (int) $class::max('order') + 1, 'publish' => true]);

		return $this->answer($kind, $record, 201);
	}

	public function update(Request $request, string $kind, string $uuid): JsonResponse
	{
		$record = $this->find($kind, $uuid);
		$record->update($this->validated($request, $kind, $record));

		return $this->answer($kind, $record);
	}

	/** Soft-deleted, and only when nothing uses it. */
	public function destroy(string $kind, string $uuid): JsonResponse
	{
		$record = $this->find($kind, $uuid);

		abort_if($this->usage($record) > 0, 422, 'Wird noch verwendet und kann nicht gelöscht werden.');

		$record->delete();

		return response()->json(status: 204);
	}

	/**
	 * German written as `['de' => …]`, so an English value stays where it is.
	 *
	 * @return array<string, mixed>
	 */
	private function validated(Request $request, string $kind, ?Model $record = null): array
	{
		$schema = $this->schema($kind);
		$data = $request->validate($schema->rules($record), $schema->messages(), $schema->attributes());
		$de = fn (string $field) => [...($record?->getTranslations($field) ?? []), 'de' => $data[$field]];

		return $kind === 'locations'
			? ['description' => $de('description'), 'address' => $de('address'), 'map' => ($data['map'] ?? '') ?: null, 'publish' => (bool) ($data['publish'] ?? false)]
			: ['title' => $de('title')];
	}

	/** Course dates at a place; courses filed under a term; and licence products under a group or a maker. */
	private function usage(Model $record): int
	{
		return match (true) {
			$record instanceof Location => $record->events()->count(),
			$record instanceof Manufacturer => $record->products()->count(),
			default => $this->courses($record) + $this->licences($record),
		};
	}

	private function courses(Model $term): int
	{
		return DB::table('course_taxonomy')->where('taxonomy_type', $term->getMorphClass())->where('taxonomy_id', $term->getKey())->count();
	}

	private function licences(Model $term): int
	{
		return $term instanceof Software || $term instanceof Manufacturer ? $term->products()->count() : 0;
	}

	/** @return array<string, mixed> */
	private function term(Model $term): array
	{
		return [
			'uuid' => $term->uuid,
			'title' => $term->getTranslation('title', 'de', false) ?: '',
			'usage' => $this->usage($term),
			'courses' => $term instanceof Manufacturer ? 0 : $this->courses($term),
			'licences' => $this->licences($term),
		];
	}

	/** @return array<string, mixed> */
	private function location(Location $location): array
	{
		return [
			'uuid' => $location->uuid,
			'description' => $location->getTranslation('description', 'de', false) ?: '',
			'address' => $location->getTranslation('address', 'de', false) ?: '',
			'map' => $location->map ?? '',
			'publish' => $location->publish,
			'usage' => $this->usage($location),
		];
	}

	private function answer(string $kind, Model $record, int $status = 200): JsonResponse
	{
		return response()->json(['data' => $kind === 'locations' ? $this->location($record) : $this->term($record)], $status);
	}

	private function find(string $kind, string $uuid): Model
	{
		return $this->model($kind)::query()->where('uuid', $uuid)->firstOrFail();
	}

	/** @return class-string<Model> */
	private function model(string $kind): string
	{
		return $kind === 'locations' ? Location::class : (self::TERMS[$kind] ?? abort(404));
	}

	private function schema(string $kind): Schema
	{
		$this->model($kind);

		return $kind === 'locations' ? new LocationSchema : new TermSchema;
	}
}
