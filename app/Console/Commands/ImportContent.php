<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Content\ContentBundle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * **`content:import`, on production after the ports** ([[ContentBundle]],
 * [[01-schema]] *Content from staging*): puts the folder `content:export`
 * wrote on staging in place of the Angebote, licences, Seiteninhalte and
 * categories here. It replaces the `catalogue:*` steps at the cutover:
 * staging already holds their result and everything VIAK did after.
 *
 * The bundle's tables are emptied and refilled with staging's rows and ids.
 * A link to a course goes to the course with the same uuid here, else the
 * same slug; one with neither is dropped and listed. `media` keeps its other
 * rows (courses, events, messages): only the bundle's models' images are
 * replaced, with new ids. Then `search:rebuild`.
 *
 * Refuses while licence orders exist: their lines point at licence variants,
 * which this replaces. At the cutover there are none yet (`port:shop` runs
 * after it).
 */
class ImportContent extends Command
{
	protected $signature = 'content:import
		{path : The folder content:export wrote}
		{--dry-run : Report what would change without writing}';

	protected $description = 'Replace the Angebote, licences, Seiteninhalte and categories with a content:export folder';

	/** @var array<int, string> */
	private array $dropped = [];

	public function handle(): int
	{
		$path = rtrim((string) $this->argument('path'), '/');
		$bundle = json_decode((string) @file_get_contents($path.'/content.json'), true);

		if (! is_array($bundle) || ! isset($bundle['tables'])) {
			$this->components->error("{$path}/content.json is missing or not an export.");

			return self::FAILURE;
		}

		if (DB::table('licence_order_items')->exists()) {
			$this->components->error('There are licence orders here; their lines point at the licence variants this would replace. Import before `port:shop`, on a database without orders.');

			return self::FAILURE;
		}

		$this->line("From {$bundle['from']}, exported {$bundle['exported_at']}.");

		$courses = $this->courseMap($bundle['courses']);
		$tables = $this->translate($bundle['tables'], $courses);

		foreach ($tables as $table => $rows) {
			$here = $table === 'media'
				? DB::table('media')->whereIn('mediable_type', ContentBundle::MEDIA_OWNERS)->count()
				: ($table === 'course_taxonomy'
					? DB::table('course_taxonomy')->whereIn('taxonomy_type', ContentBundle::COURSE_TAXONOMY_TYPES)->count()
					: DB::table($table)->count());
			$this->line(sprintf('  %-26s %5d here, %5d from the export', $table, $here, count($rows)));
		}

		foreach ($this->dropped as $line) {
			$this->components->warn($line);
		}

		if ($this->option('dry-run')) {
			$this->components->info('Dry run: nothing written.');

			return self::SUCCESS;
		}

		Schema::withoutForeignKeyConstraints(function () use ($tables): void {
			DB::transaction(function () use ($tables): void {
				$this->replace($tables);
			});
		});

		$files = $this->copyFiles($path.'/files');

		$this->call('search:rebuild');
		$this->components->info("Imported, with {$files} files.");

		return self::SUCCESS;
	}

	/**
	 * Staging's course id => this database's, by uuid, else by slug.
	 *
	 * @param  array<int|string, array{uuid: string, slug: string|null}>  $courses
	 * @return array<int, int|null>
	 */
	private function courseMap(array $courses): array
	{
		$byUuid = DB::table('courses')->pluck('id', 'uuid');
		$bySlug = DB::table('courses')->get(['id', 'slug'])
			->mapWithKeys(fn ($course) => [json_decode((string) $course->slug, true)['de'] ?? '' => $course->id]);

		$map = [];

		foreach ($courses as $id => $course) {
			$map[(int) $id] = $byUuid[$course['uuid']] ?? ($course['slug'] !== null ? ($bySlug[$course['slug']] ?? null) : null);

			if ($map[(int) $id] === null) {
				$this->dropped[] = "course {$course['slug']} ({$course['uuid']}) is not here: its links are dropped";
			}
		}

		return $map;
	}

	/**
	 * The rows with their course links pointed at this database's courses;
	 * a row whose course is not here is left out.
	 *
	 * @param  array<string, list<array<string, mixed>>>  $tables
	 * @param  array<int, int|null>  $courses
	 * @return array<string, list<array<string, mixed>>>
	 */
	private function translate(array $tables, array $courses): array
	{
		$references = [...ContentBundle::TABLES, 'course_taxonomy' => [['course_id', null]], 'media' => []];
		$out = [];

		foreach ($references as $table => $columns) {
			$out[$table] = [];

			foreach ($tables[$table] ?? [] as $row) {
				foreach ($columns as [$idColumn, $typeColumn]) {
					if ($typeColumn !== null && $row[$typeColumn] !== ContentBundle::COURSE) {
						continue;
					}

					$row[$idColumn] = $courses[(int) $row[$idColumn]] ?? null;

					if ($row[$idColumn] === null) {
						continue 2;
					}
				}

				// Shared tables: their other rows stay, so these take new ids.
				if ($table === 'media' || $table === 'course_taxonomy') {
					unset($row['id']);
				}

				$out[$table][] = $row;
			}
		}

		return $out;
	}

	/** @param array<string, list<array<string, mixed>>> $tables */
	private function replace(array $tables): void
	{
		foreach (array_reverse(array_keys(ContentBundle::TABLES)) as $table) {
			DB::table($table)->delete();
		}

		DB::table('course_taxonomy')->whereIn('taxonomy_type', ContentBundle::COURSE_TAXONOMY_TYPES)->delete();
		DB::table('media')->whereIn('mediable_type', ContentBundle::MEDIA_OWNERS)->delete();

		foreach ($tables as $table => $rows) {
			foreach (array_chunk($rows, 200) as $chunk) {
				DB::table($table)->insert($chunk);
			}
		}
	}

	private function copyFiles(string $source): int
	{
		if (! File::isDirectory($source)) {
			return 0;
		}

		$disk = Storage::disk('public');
		$copied = 0;

		foreach (File::allFiles($source) as $file) {
			$disk->put($file->getRelativePathname(), $file->getContents());
			$copied++;
		}

		return $copied;
	}
}
