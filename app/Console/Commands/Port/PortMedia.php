<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use App\Actions\Media\NormalizeImage;
use App\Models\Course;
use App\Models\Media;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Ports the images ([[08-accounts]]).
 *
 * Reads the legacy `images` table and the **production storage snapshot** —
 * `LEGACY_STORAGE_PATH`, the read-only `legacy` disk. It cannot run without the
 * files, and not because of the copying: `media` needs `width` and `height`, and
 * legacy's table never stored them.
 *
 * ## What carries across unchanged
 *
 * The crop. Legacy's `coords_w/h/x/y` are `double(16,12)`, which reads like
 * normalised fractions and is not — the values are **pixels**, ranging to
 * 4608×3124, and `MarceliTo\ImageCache\Templates\Crop` hands them straight to
 * `Intervention::crop()` as `width,height,x,y`. Glide's `crop` parameter takes
 * the same four values in the same order.
 *
 * ## Three things that do not
 *
 * **`0,0,0,0` is not a crop.** 85 of the 492 rows hold it, and legacy
 * special-cased the literal string to avoid rendering a 1×1 image. They become
 * `null`. No row is half-zero, so the test is clean.
 *
 * **Normalising rescales the crop.** Sources over 3200 px are shrunk, and the
 * crop is in source pixels — **29 of the 407 cropped images** come from a larger
 * source. [[NormalizeImage]] returns the factor it applied and every coordinate
 * is multiplied by it. Without that the image still renders, just cropped
 * somewhere else, which nobody would notice.
 *
 * **`orientation` is wrong on 49 of 333 rows.** Measured against the files
 * themselves. It is derived from the dimensions instead, and the column is not
 * ported.
 *
 * ## What is skipped, and why that is fine
 *
 * Soft-deleted rows. All 159 of them have **no file on disk** — deleting an
 * image in legacy deleted the file — so there is nothing to port. And images on
 * `Hero` and `News`, which are chunk 04's models and do not exist yet; those are
 * reported rather than dropped silently, so the count is accounted for.
 */
class PortMedia extends Command
{
	protected $signature = 'port:media {--dry-run : Report what would change without writing}';

	protected $description = 'Port images from the legacy database and the production storage snapshot';

	/** @var array<int, string> */
	private array $findings = [];

	/** @var array<int, string> */
	private array $observations = [];

	public function handle(NormalizeImage $normalize): int
	{
		$legacy = DB::connection('legacy');
		$snapshot = config('filesystems.disks.legacy.root');

		if (! is_dir($snapshot.'/uploads')) {
			$this->error("No legacy storage snapshot at {$snapshot}/uploads. Set LEGACY_STORAGE_PATH; see the snapshot's PROVENANCE.md.");

			return self::FAILURE;
		}

		$dryRun = (bool) $this->option('dry-run');

		$owners = [
			'App\Models\Course' => Course::pluck('id', 'uuid'),
			'App\Models\User' => User::pluck('id', 'uuid'),
		];
		$legacyUuids = [
			'App\Models\Course' => $legacy->table('courses')->pluck('uuid', 'id'),
			'App\Models\User' => $legacy->table('users')->pluck('uuid', 'id'),
		];

		$ported = 0;
		$cropped = 0;
		$rescaled = 0;
		$skipped = 0;

		foreach ($legacy->table('images')->whereNull('deleted_at')->orderBy('id')->get() as $row) {
			$type = $row->imageable_type;

			if (! isset($owners[$type])) {
				$this->observations[] = "image {$row->id}: {$type} has no rework model yet (chunk 04); skipped";
				$skipped++;

				continue;
			}

			$ownerUuid = $legacyUuids[$type][$row->imageable_id] ?? null;
			$ownerId = $ownerUuid === null ? null : ($owners[$type][$ownerUuid] ?? null);

			if ($ownerId === null) {
				// Two different situations, and only one of them is news.
				//
				// The owner row is gone from the legacy table entirely: an
				// orphan, nothing to attach the image to, expected. Legacy user
				// 8 is the live example — images 36 and 38 are an expert
				// portrait for somebody who was hard-deleted.
				//
				// The owner exists in legacy but not in the rework: that is a
				// gap in an earlier port and wants looking at. `port:users`
				// leaves the 5 soft-deleted users behind on purpose, so this
				// catches anything beyond those.
				$short = class_basename($type);

				if ($ownerUuid === null) {
					$this->observations[] = "image {$row->id}: {$short} {$row->imageable_id} no longer exists in legacy either; skipped as an orphan";
				} else {
					$this->findings[] = "image {$row->id}: {$short} {$ownerUuid} is in legacy but not in the rework; skipped";
				}

				$skipped++;

				continue;
			}

			$source = $snapshot.'/uploads/'.$row->name;

			if (! is_file($source)) {
				$this->findings[] = "image {$row->id} ({$row->name}): no file in the storage snapshot; skipped";
				$skipped++;

				continue;
			}

			// Counted before the dry-run exit, so a dry run reports the crop
			// figures too — they are the numbers worth checking before a real
			// run writes 306 files.
			$hasCrop = (float) $row->coords_w > 0 && (float) $row->coords_h > 0;
			$oversized = max((float) $row->coords_w + (float) $row->coords_x, (float) $row->coords_h + (float) $row->coords_y) > NormalizeImage::MAX_EDGE;

			if ($hasCrop) {
				$cropped++;
			}

			if ($dryRun) {
				if ($hasCrop && $oversized) {
					$rescaled++;
				}

				$ported++;

				continue;
			}

			Storage::disk('public')->put('uploads/'.$row->name, (string) file_get_contents($source));
			$path = Storage::disk('public')->path('uploads/'.$row->name);

			// Order matters: normalise first, then read the dimensions, so what
			// is stored describes the file as it now is — and scale the crop by
			// the factor the normaliser reports.
			$scale = $normalize->execute($path, $this->mimeType($path)) ?? 1.0;
			[$width, $height] = @getimagesize($path) ?: [null, null];

			$crop = $this->crop($row, $scale);

			if ($scale < 1.0 && $crop !== null) {
				$rescaled++;
				$this->observations[] = sprintf(
					'image %d (%s): source rescaled ×%.4f, crop scaled with it',
					$row->id, $row->name, $scale,
				);
			}

			Media::create([
				'uuid' => $row->uuid,
				'mediable_type' => $type === 'App\Models\Course' ? Course::class : User::class,
				'mediable_id' => $ownerId,
				'file' => $row->name,
				'original_name' => $row->original_name,
				'mime_type' => $this->mimeType($path),
				'size' => (int) (@filesize($path) ?: 0),
				'alt' => null,
				'caption' => $row->caption,
				'width' => $width,
				'height' => $height,
				'crop' => $crop,
				'variant' => 'desktop',
				'is_teaser' => false,
				'is_og' => false,
				'sort_order' => max(0, (int) $row->order),
			]);

			$ported++;
		}

		$this->report($ported, $cropped, $rescaled, $skipped, $dryRun);

		return self::SUCCESS;
	}

	/**
	 * Legacy's four columns, in pixels, scaled by whatever the normaliser did.
	 *
	 * @return array{x: int, y: int, w: int, h: int}|null
	 */
	private function crop(object $row, float $scale): ?array
	{
		if ((float) $row->coords_w <= 0 || (float) $row->coords_h <= 0) {
			return null;
		}

		return [
			'x' => (int) round((float) $row->coords_x * $scale),
			'y' => (int) round((float) $row->coords_y * $scale),
			'w' => (int) round((float) $row->coords_w * $scale),
			'h' => (int) round((float) $row->coords_h * $scale),
		];
	}

	/**
	 * Read from the file, not from the `extension` column. Legacy's is
	 * `varchar(4)` and describes what the file was called, not what it is.
	 */
	private function mimeType(string $path): ?string
	{
		return @mime_content_type($path) ?: null;
	}

	private function report(int $ported, int $cropped, int $rescaled, int $skipped, bool $dryRun): void
	{
		$this->newLine();
		$this->table(['', ''], [
			[$dryRun ? 'Would port' : 'Ported', $ported],
			['— carrying a crop', $cropped],
			['— crop rescaled with the source', $rescaled],
			['Skipped', $skipped],
		]);

		foreach (['findings' => 'error', 'observations' => 'line'] as $bucket => $style) {
			if ($this->{$bucket} === []) {
				continue;
			}

			$this->newLine();
			$this->line(ucfirst($bucket).':');

			foreach ($this->{$bucket} as $entry) {
				$this->{$style}('  '.$entry);
			}
		}
	}
}
