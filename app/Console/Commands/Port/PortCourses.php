<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use App\Enums\EventState;
use App\Models\Category;
use App\Models\Course;
use App\Models\Event;
use App\Models\Language;
use App\Models\Level;
use App\Models\Location;
use App\Models\Software;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Ports courses, events and their taxonomies out of the live database
 * ([[01-schema]]). Idempotent: truncates the target tables and re-imports,
 * so it can be re-run against a fresher dump as often as needed.
 *
 * Reconciliation is the point, not the copying. Every mismatch it reports is
 * a decision someone has to make before cutover.
 */
class PortCourses extends Command
{
	protected $signature = 'port:courses {--dry-run : Report what would change without writing}';

	protected $description = 'Port courses, events and taxonomies from the legacy database';

	/** @var array<int, string> */
	private array $findings = [];

	public function handle(): int
	{
		$legacy = DB::connection('legacy');

		if ($this->option('dry-run')) {
			$this->reconcile($legacy);

			return self::SUCCESS;
		}

		DB::transaction(function () use ($legacy): void {
			$this->clear();

			$taxonomyMap = $this->portTaxonomies($legacy);
			$locationMap = $this->portLocations($legacy);
			$courseMap = $this->portCourses($legacy, $taxonomyMap);
			$this->portEvents($legacy, $courseMap, $locationMap);
		});

		$this->reconcile($legacy);

		return self::SUCCESS;
	}

	/**
	 * Deletes rather than truncates: TRUNCATE causes an implicit commit in
	 * MySQL, which would silently end the surrounding transaction and leave a
	 * failed port half-applied. Child tables first, so foreign keys hold.
	 */
	private function clear(): void
	{
		foreach (['course_taxonomy', 'event_dates', 'event_expert', 'events', 'courses', 'locations', 'categories', 'levels', 'languages', 'software', 'tags'] as $table) {
			DB::table($table)->delete();
		}
	}

	/** @return array<string, array<int, int>> legacy id => new id, per taxonomy */
	private function portTaxonomies($legacy): array
	{
		$map = [];

		foreach ([
			'categories' => Category::class,
			'levels' => Level::class,
			'languages' => Language::class,
			'software' => Software::class,
			'tags' => Tag::class,
		] as $table => $model) {
			foreach ($legacy->table($table)->whereNull('deleted_at')->get() as $row) {
				// Legacy calls the label `description` on every taxonomy table,
				// and has neither an `order` nor a `publish` column for them.
				$created = $model::create([
					'title' => $this->translated($row->description),
					'order' => $row->order ?? 0,
					'publish' => (bool) ($row->publish ?? true),
				]);

				if (blank($created->getTranslations('title'))) {
					$this->findings[] = "{$table} {$row->id}: imported with no title";
				}

				$map[$table][$row->id] = $created->id;
			}
		}

		return $map;
	}

	/** @return array<int, int> */
	private function portLocations($legacy): array
	{
		$map = [];

		foreach ($legacy->table('locations')->whereNull('deleted_at')->get() as $row) {
			$map[$row->id] = Location::create([
				'description' => $this->translated($row->description),
				'address' => $this->translated($row->address),
				'map' => $row->map,
				'publish' => (bool) $row->publish,
			])->id;
		}

		return $map;
	}

	/**
	 * @param  array<string, array<int, int>>  $taxonomyMap
	 * @return array<int, int>
	 */
	private function portCourses($legacy, array $taxonomyMap): array
	{
		$map = [];

		foreach ($legacy->table('courses')->whereNull('deleted_at')->orderBy('id')->get() as $row) {
			$course = Course::create([
				'number' => $row->number,
				'slug' => $this->translated($row->slug),
				'title' => $this->translated($row->title),
				'subtitle' => $this->translated($row->subtitle),
				'summary' => $this->translated($row->summary),
				'short_description' => $this->translated($row->short_description),
				'full_description' => $this->translated($row->full_description),
				// additional_information -> booking terms; _1 -> course content.
				'information_booking' => $this->translated($row->additional_information),
				'information_content' => $this->translated($row->additional_information_1),
				'facts' => array_values(array_filter([
					$this->translated($row->facts_column_1),
					$this->translated($row->facts_column_2),
					$this->translated($row->facts_column_3),
				])),
				'fee' => $row->fee ?? 0,
				'reviews' => $this->maybeJson($row->reviews),
				'seo_description' => $this->translated($row->seo_description),
				'seo_tags' => $this->translated($row->seo_tags),
				'online' => (bool) $row->online,
				'publish' => (bool) $row->publish,
				'order' => $row->order,
			]);

			$map[$row->id] = $course->id;

			foreach ([
				'category_course' => ['categories', 'category_id'],
				'course_level' => ['levels', 'level_id'],
				'course_language' => ['languages', 'language_id'],
				'course_software' => ['software', 'software_id'],
				'course_tag' => ['tags', 'tag_id'],
			] as $pivot => [$relation, $foreignKey]) {
				$ids = $legacy->table($pivot)
					->where('course_id', $row->id)
					->pluck($foreignKey)
					->map(fn ($id) => $taxonomyMap[$relation][$id] ?? null)
					->filter()
					->all();

				$course->{$relation}()->sync($ids);
			}
		}

		return $map;
	}

	/**
	 * @param  array<int, int>  $courseMap
	 * @param  array<int, int>  $locationMap
	 */
	private function portEvents($legacy, array $courseMap, array $locationMap): void
	{
		foreach ($legacy->table('events')->whereNull('deleted_at')->orderBy('id')->get() as $row) {
			if (! isset($courseMap[$row->course_id])) {
				$this->findings[] = "event {$row->id}: course {$row->course_id} is soft-deleted; event skipped";

				continue;
			}

			$dates = $legacy->table('event_dates')->where('event_id', $row->id)->orderBy('date')->get();

			// `events.date` is nullable in legacy, which let half-created events
			// be published with no date at all. They are invisible on the live
			// site (a NULL date fails both the upcoming and past comparisons),
			// so they are junk rather than data. The new schema forbids it.
			if ($dates->isEmpty() && $row->date === null) {
				$this->findings[] = "event {$row->id}: published but has neither event_dates nor events.date; skipped as unusable";

				continue;
			}

			if ($dates->isEmpty()) {
				$this->findings[] = "event {$row->id}: no event_dates rows; falling back to events.date";
			}

			// A batch entered on 2025-06-03 was typed with two-digit years, so
			// `events.date` landed in year 0025 while the event_dates rows fell
			// back to the creation date. Neither column holds the real date,
			// and guessing one would put a wrong date in front of students.
			if ($row->date !== null && (int) substr((string) $row->date, 0, 4) < 1000) {
				$intended = '2'.substr((string) $row->date, 1, 3);
				$placeholder = $dates->isNotEmpty()
					&& str_starts_with((string) $row->created_at, (string) $dates->first()->date);

				$this->findings[] = sprintf(
					'event %d: events.date year is %s (typed as two digits; intended %s-%s). event_dates say %s%s. NEEDS A DECISION — skipped',
					$row->id,
					substr((string) $row->date, 0, 4),
					$intended,
					substr((string) $row->date, 5),
					$dates->first()->date ?? 'nothing',
					$placeholder ? ' (equals created_at, so a placeholder)' : ' (looks deliberate)',
				);

				continue;
			}

			$state = $this->resolveState($legacy, $row);

			$event = Event::create([
				'date' => $dates->first()->date ?? $row->date,
				'registration_until' => $row->registration_until,
				'min_participants' => $row->min_participants,
				'max_participants' => $row->max_participants,
				'state' => $state,
				'confirmed_at' => $row->confirmed_at,
				'cancelled_at' => $row->cancelled_at,
				'closed_at' => $row->closed_at,
				'rentals_available' => (bool) $row->rentals_available,
				'online' => (bool) $row->online,
				'free_of_charge' => (bool) $row->free_of_charge,
				'publish' => (bool) $row->publish,
				'fee' => $row->fee,
				'course_id' => $courseMap[$row->course_id],
				'location_id' => $locationMap[$row->location_id] ?? null,
			]);

			foreach ($dates as $date) {
				$event->dates()->create([
					'date' => $date->date,
					'time_start' => $date->time_start,
					'time_end' => $date->time_end,
				]);
			}

			if ($dates->isNotEmpty() && $row->date !== $dates->first()->date) {
				$this->findings[] = "event {$row->id}: events.date ({$row->date}) differed from its first event_date ({$dates->first()->date}); first date wins";
			}
		}
	}

	/**
	 * Legacy state lived in flags AND timestamp columns. Flags win, because
	 * that is what the running application read — but any disagreement is
	 * reported rather than silently resolved.
	 */
	private function resolveState($legacy, object $row): EventState
	{
		$flags = $legacy->table('flags')
			->where('flaggable_type', 'App\\Models\\Event')
			->where('flaggable_id', $row->id)
			->pluck('name')
			->all();

		foreach ([
			'isCancelled' => [EventState::Cancelled, 'cancelled_at'],
			'isClosed' => [EventState::Closed, 'closed_at'],
			'isConfirmed' => [EventState::Confirmed, 'confirmed_at'],
		] as $flag => [$state, $column]) {
			$hasFlag = in_array($flag, $flags, true);
			$hasStamp = $row->{$column} !== null;

			if ($hasFlag !== $hasStamp) {
				$this->findings[] = sprintf(
					'event %d: flag %s=%s but %s=%s',
					$row->id,
					$flag,
					$hasFlag ? 'yes' : 'no',
					$column,
					$hasStamp ? $row->{$column} : 'null',
				);
			}

			if ($hasFlag) {
				return $state;
			}
		}

		return EventState::Planned;
	}

	private function reconcile($legacy): void
	{
		$this->newLine();
		$this->components->info('Reconciliation');

		$rows = [];

		foreach ([
			'courses' => Course::class,
			'events' => Event::class,
			'locations' => Location::class,
		] as $table => $model) {
			$before = $legacy->table($table)->whereNull('deleted_at')->count();
			$after = $model::count();
			$skipped = $before - $after;
			$rows[] = [
				$table,
				$before,
				$after,
				match (true) {
					$skipped === 0 => 'ok',
					$skipped > 0 => $skipped.' skipped, see findings',
					default => 'MISMATCH',
				},
			];
		}

		$this->table(['table', 'legacy', 'ported', ''], $rows);

		if ($this->findings === []) {
			$this->components->info('No data-quality findings.');

			return;
		}

		$this->newLine();
		$this->components->warn(count($this->findings).' finding(s) needing a decision before cutover:');

		foreach ($this->findings as $finding) {
			$this->line('  - '.$finding);
		}
	}

	/** @return array<string, string>|null */
	private function translated(?string $value): ?array
	{
		if (blank($value)) {
			return null;
		}

		$decoded = json_decode($value, true);

		// Legacy holds a few plain strings in otherwise-translatable columns.
		$translations = is_array($decoded) ? $decoded : ['de' => $value];

		return array_filter(array_map(
			fn ($text) => is_string($text) ? $this->normaliseRichText($text) : $text,
			$translations,
		), 'filled') ?: null;
	}

	/**
	 * TinyMCE wrote entity-encoded HTML: `&auml;` rather than `ä`, in a utf8mb4
	 * column that never needed it. 24 of 35 courses are affected. Decoding on
	 * the way in means the editor that replaces TinyMCE ([[07-editor]]) starts
	 * from clean UTF-8 instead of inheriting the encoding forever.
	 */
	private function normaliseRichText(string $text): string
	{
		$decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		return trim($decoded);
	}

	private function maybeJson(?string $value): ?array
	{
		if (blank($value)) {
			return null;
		}

		$decoded = json_decode($value, true);

		return is_array($decoded) ? $decoded : null;
	}
}
