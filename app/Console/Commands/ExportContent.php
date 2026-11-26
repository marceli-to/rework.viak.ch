<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Software;
use App\Support\Content\ContentBundle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * **`content:export`, on staging** ([[ContentBundle]], [[01-schema]] *Content
 * from staging*): the Angebote, licences, Seiteninhalte and categories as VIAK
 * left them, into one folder that `content:import` puts onto production after
 * the ports.
 *
 * The folder holds `content.json` (the rows, as stored, plus the uuid and slug
 * of every course they point at) and `files/` (the images and logos, at their
 * paths on the public disk). Nothing is changed here.
 */
class ExportContent extends Command
{
	protected $signature = 'content:export {path? : Folder to write (default storage/app/private/content/<date>)}';

	protected $description = 'Export the Angebote, licences, Seiteninhalte and categories for content:import';

	public function handle(): int
	{
		$path = $this->argument('path') ?: storage_path('app/private/content/'.now()->format('Y-m-d-His'));

		if (File::exists($path) && File::files($path) !== []) {
			$this->components->error("{$path} is not empty.");

			return self::FAILURE;
		}

		File::ensureDirectoryExists($path.'/files');

		$tables = [];
		$courseIds = [];

		foreach (ContentBundle::TABLES as $table => $references) {
			$rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
			$tables[$table] = $rows;

			foreach ($rows as $row) {
				foreach ($references as [$idColumn, $typeColumn]) {
					if ($typeColumn === null || $row[$typeColumn] === ContentBundle::COURSE) {
						$courseIds[] = $row[$idColumn];
					}
				}
			}
		}

		$tables['course_taxonomy'] = DB::table('course_taxonomy')
			->whereIn('taxonomy_type', ContentBundle::COURSE_TAXONOMY_TYPES)
			->get()->map(fn ($row) => (array) $row)->all();
		$courseIds = [...$courseIds, ...array_column($tables['course_taxonomy'], 'course_id')];

		$tables['media'] = DB::table('media')
			->whereIn('mediable_type', ContentBundle::MEDIA_OWNERS)
			->get()->map(fn ($row) => (array) $row)->all();

		$courses = DB::table('courses')->whereIn('id', array_unique($courseIds))->get(['id', 'uuid', 'slug'])
			->mapWithKeys(fn ($course) => [$course->id => [
				'uuid' => $course->uuid,
				'slug' => json_decode((string) $course->slug, true)['de'] ?? null,
			]])->all();

		$files = $this->copyFiles($tables, $path.'/files');

		File::put($path.'/content.json', json_encode([
			'exported_at' => now()->toIso8601String(),
			'from' => config('app.url'),
			'tables' => $tables,
			'courses' => $courses,
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

		foreach ($tables as $table => $rows) {
			$this->line(sprintf('  %-26s %5d', $table, count($rows)));
		}

		$this->components->info("Exported to {$path}, with {$files} files.");

		return self::SUCCESS;
	}

	/** The images and logos, at their paths on the public disk. */
	private function copyFiles(array $tables, string $target): int
	{
		$disk = Storage::disk('public');
		$paths = [
			...array_map(fn (array $media) => 'uploads/'.$media['file'], $tables['media']),
			...array_map(fn (string $logo) => Software::LOGOS.'/'.$logo, array_filter(array_column($tables['software'], 'logo'))),
		];
		$copied = 0;

		foreach (array_unique($paths) as $file) {
			if (! $disk->exists($file)) {
				$this->components->warn("{$file}: not on the public disk; the row goes without it");

				continue;
			}

			File::ensureDirectoryExists(dirname($target.'/'.$file));
			File::copy($disk->path($file), $target.'/'.$file);
			$copied++;
		}

		return $copied;
	}
}
