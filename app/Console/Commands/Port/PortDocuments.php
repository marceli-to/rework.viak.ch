<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use App\Enums\DocumentType;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Ports the generated PDFs ([[08-accounts]]). Runs after `port:invoices`.
 *
 * Reads the legacy `user_documents` table and the production storage snapshot,
 * and moves 1,005 files off the public disk onto the private one.
 *
 * ## Nothing is missing, and that took a snapshot to establish
 *
 * Reconciled against production on 2026-09-18: **all 1,162 rows have a file.**
 * An earlier count of 495 "missing" was an artefact of an incomplete local copy.
 *
 * What the failures actually were is two bugs, both confined to 2023
 * participation confirmations, and this port fixes both.
 *
 * ## 1. 271 rows store a malformed path
 *
 *     stored     /storage/filesf962c8c4-…/viak-teilnahmebestaetigung-….pdf
 *     on disk    /storage/files/f962c8c4-…/viak-teilnahmebestaetigung-….pdf
 *                              ^ the separator
 *
 * Put the slash back and **all 271 resolve**. `EventParticipationConfirmation`
 * builds the path correctly today, so this is an older defect whose rows were
 * never repaired — and **95 students have had 404ing links since 2023**. Since
 * the rework derives the path from the owner and the filename rather than
 * storing it, the class of bug goes away with the column.
 *
 * ## 2. 157 rows are duplicates
 *
 * 17 paths carry more than one row — 37 for a single booking, then 19, 19, 18,
 * 12 — so 2023's 272 rows sit on **115 actual files**. Each group is one booking
 * and one user, which is the signature of `EventClosedStudent` being re-run:
 * that Mailable generated the PDF and inserted the row from inside its own
 * constructor, so every pass through the queue wrote another.
 *
 * A student who took a course in 2023 currently opens *Meine Dokumente* and sees
 * up to 37 identical entries, none of which download. **Deduplicate on the
 * file**, not on the row, and do not trust `created_at` to tell documents apart —
 * the duplicates share a file but not a timestamp.
 */
class PortDocuments extends Command
{
	protected $signature = 'port:documents {--dry-run : Report what would change without writing}';

	protected $description = 'Port generated PDFs from the legacy database and the production storage snapshot';

	/** @var array<int, string> */
	private array $findings = [];

	/** @var array<int, string> */
	private array $observations = [];

	public function handle(): int
	{
		$legacy = DB::connection('legacy');
		$snapshot = config('filesystems.disks.legacy.root');

		if (! is_dir($snapshot.'/files')) {
			$this->error("No legacy storage snapshot at {$snapshot}/files. Set LEGACY_STORAGE_PATH; see the snapshot's PROVENANCE.md.");

			return self::FAILURE;
		}

		$dryRun = (bool) $this->option('dry-run');

		$users = User::pluck('id', 'uuid');
		$legacyUsers = $legacy->table('users')->pluck('uuid', 'id');
		$invoices = Invoice::withTrashed()->pluck('id', 'uuid');
		$legacyInvoices = $legacy->table('invoices')->pluck('uuid', 'id');
		$bookings = Booking::withTrashed()->pluck('id', 'uuid');
		$legacyBookings = $legacy->table('bookings')->pluck('uuid', 'id');

		$seen = [];
		$ported = 0;
		$repaired = 0;
		$duplicates = 0;
		$skipped = 0;

		foreach ($legacy->table('user_documents')->orderBy('id')->get() as $row) {
			$userUuid = $legacyUsers[$row->user_id] ?? null;
			$userId = $userUuid === null ? null : ($users[$userUuid] ?? null);

			if ($userId === null) {
				$this->findings[] = "user_document {$row->id}: user {$row->user_id} not in the rework; skipped";
				$skipped++;

				continue;
			}

			$uri = $this->repair((string) $row->uri);

			if ($uri !== $row->uri) {
				$repaired++;
			}

			$filename = basename($uri);

			// Deduplicate on the file, not the row. 157 surplus rows across 17
			// paths, all of them the same document written again.
			$key = $userId.'/'.$filename;

			if (isset($seen[$key])) {
				$duplicates++;

				continue;
			}

			$source = $snapshot.str_replace('/storage', '', '/'.ltrim($uri, '/'));

			if (! is_file($source)) {
				$this->findings[] = "user_document {$row->id} ({$filename}): no file in the storage snapshot; skipped";
				$skipped++;

				continue;
			}

			$seen[$key] = true;

			if ($dryRun) {
				$ported++;

				continue;
			}

			Storage::disk('documents')->put(
				'documents/'.$userUuid.'/'.$filename,
				(string) file_get_contents($source),
			);

			[$type, $documentable] = $this->documentable(
				$row, $legacyInvoices, $invoices, $legacyBookings, $bookings,
			);

			UserDocument::create([
				'uuid' => $row->uuid,
				'user_id' => $userId,
				'type' => $type,
				'filename' => $filename,
				// Legacy's own `date`, not `created_at` — the duplicates share a
				// file but not a creation timestamp, so the latter identifies
				// nothing.
				'date' => $row->date,
				'documentable_type' => $documentable[0],
				'documentable_id' => $documentable[1],
			]);

			$ported++;
		}

		$this->report($ported, $repaired, $duplicates, $skipped, $dryRun);

		return self::SUCCESS;
	}

	/**
	 * Puts back the separator legacy dropped between `files` and the user uuid.
	 *
	 * 271 rows, every one a 2023 participation confirmation. Anchored on a uuid
	 * so it cannot touch a path that is already correct.
	 */
	private function repair(string $uri): string
	{
		return (string) preg_replace('#/files(?=[0-9a-f]{8}-[0-9a-f]{4}-)#', '/files/', $uri);
	}

	/**
	 * @return array{0: DocumentType, 1: array{0: ?string, 1: ?int}}
	 */
	private function documentable(
		object $row,
		mixed $legacyInvoices,
		mixed $invoices,
		mixed $legacyBookings,
		mixed $bookings,
	): array {
		$type = DocumentType::from($row->type);

		$uuid = match ($row->fileable_type) {
			'App\Models\Invoice' => $legacyInvoices[$row->fileable_id] ?? null,
			'App\Models\Booking' => $legacyBookings[$row->fileable_id] ?? null,
			default => null,
		};

		$id = match ($row->fileable_type) {
			'App\Models\Invoice' => $uuid === null ? null : ($invoices[$uuid] ?? null),
			'App\Models\Booking' => $uuid === null ? null : ($bookings[$uuid] ?? null),
			default => null,
		};

		$class = match ($row->fileable_type) {
			'App\Models\Invoice' => Invoice::class,
			'App\Models\Booking' => Booking::class,
			default => null,
		};

		if ($id === null && $row->fileable_type !== null) {
			// Kept rather than dropped: the PDF is a document the customer
			// holds, and it is still theirs even when the row it pointed at did
			// not survive the port.
			$this->observations[] = "user_document {$row->id}: {$row->fileable_type} {$row->fileable_id} not in the rework; kept without a link";
		}

		return [$type, [$id === null ? null : $class, $id]];
	}

	private function report(int $ported, int $repaired, int $duplicates, int $skipped, bool $dryRun): void
	{
		$this->newLine();
		$this->table(['', ''], [
			[$dryRun ? 'Would port' : 'Ported', $ported],
			['— path repaired (missing separator)', $repaired],
			['Duplicate rows collapsed', $duplicates],
			['Skipped', $skipped],
		]);

		foreach (['findings' => 'error', 'observations' => 'line'] as $bucket => $style) {
			if ($this->{$bucket} === []) {
				continue;
			}

			$this->newLine();
			$this->line(ucfirst($bucket).':');

			foreach (array_slice($this->{$bucket}, 0, 10) as $entry) {
				$this->{$style}('  '.$entry);
			}

			if (count($this->{$bucket}) > 10) {
				$this->line('  … and '.(count($this->{$bucket}) - 10).' more');
			}
		}
	}
}
