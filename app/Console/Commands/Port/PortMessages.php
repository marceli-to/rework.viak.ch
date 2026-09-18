<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use App\Models\Event;
use App\Models\Media;
use App\Models\Message;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Ports the course notes and their attachments ([[08-accounts]]).
 *
 * Runs after `port:users` and `port:courses`. 251 messages, 1,105 recipient
 * rows, and the 33 file attachments that legacy kept in a separate `files` table
 * with its own pivot.
 *
 * ## The attachments fold into `media`
 *
 * Legacy had **44 files, 33 attachments and 11 files attached to nothing at
 * all** — against 492 images in a near-identical table. That does not justify a
 * module, so `files` and `fileables` become `media` rows like everything else:
 * 20 on messages, 13 on events.
 *
 * ## Two things the port drops
 *
 * `messageable_type`, which held `App\Models\Event` on all 251 rows. A
 * polymorphic relation with one possible value is a guess about the future
 * priced as complexity today, so the rework has an `event_id`.
 *
 * And the `order`, `publish` and `locked` columns on `files`, which are shelf
 * furniture from the image table the file table was copied from.
 */
class PortMessages extends Command
{
	protected $signature = 'port:messages {--dry-run : Report what would change without writing}';

	protected $description = 'Port course messages, their recipients and their attachments';

	/** @var array<int, string> */
	private array $findings = [];

	/** @var array<int, string> */
	private array $observations = [];

	public function handle(): int
	{
		$legacy = DB::connection('legacy');
		$snapshot = config('filesystems.disks.legacy.root');
		$dryRun = (bool) $this->option('dry-run');

		$users = User::pluck('id', 'uuid');
		$legacyUsers = $legacy->table('users')->pluck('uuid', 'id');
		$events = Event::withTrashed()->pluck('id', 'uuid');
		$legacyEvents = $legacy->table('events')->pluck('uuid', 'id');

		$messageMap = [];
		$ported = 0;
		$recipients = 0;
		$skipped = 0;

		foreach ($legacy->table('messages')->orderBy('id')->get() as $row) {
			$eventUuid = $legacyEvents[$row->messageable_id] ?? null;
			$eventId = $eventUuid === null ? null : ($events[$eventUuid] ?? null);
			$authorUuid = $legacyUsers[$row->user_id] ?? null;
			$authorId = $authorUuid === null ? null : ($users[$authorUuid] ?? null);

			if ($eventId === null || $authorId === null) {
				$this->findings[] = "message {$row->id}: "
					.($eventId === null ? "event {$row->messageable_id}" : "author {$row->user_id}")
					.' not in the rework; skipped';
				$skipped++;

				continue;
			}

			if ($dryRun) {
				$ported++;

				continue;
			}

			$message = Message::create([
				'uuid' => $row->uuid,
				'event_id' => $eventId,
				'user_id' => $authorId,
				'subject' => $row->subject,
				'body' => (string) $row->body,
			]);

			$message->forceFill([
				'created_at' => $row->created_at,
				'updated_at' => $row->updated_at,
				'deleted_at' => $row->deleted_at,
			])->save();

			$messageMap[$row->id] = $message->id;
			$ported++;
		}

		// Who each message actually went to, exactly as recorded. Not recomputed
		// from today's bookings, which would rewrite history for every seat
		// cancelled since.
		if (! $dryRun) {
			foreach ($legacy->table('message_user')->get() as $row) {
				$messageId = $messageMap[$row->message_id] ?? null;
				$userUuid = $legacyUsers[$row->user_id] ?? null;
				$userId = $userUuid === null ? null : ($users[$userUuid] ?? null);

				if ($messageId === null || $userId === null) {
					continue;
				}

				DB::table('message_user')->insertOrIgnore([
					'message_id' => $messageId,
					'user_id' => $userId,
					'created_at' => $row->created_at,
				]);

				$recipients++;
			}
		}

		$attachments = $this->portAttachments($legacy, $snapshot, $messageMap, $events, $legacyEvents, $dryRun);

		$this->report($ported, $recipients, $attachments, $skipped, $dryRun);

		return self::SUCCESS;
	}

	/**
	 * Legacy `files` + `fileables` become `media`.
	 *
	 * @param  array<int, int>  $messageMap
	 */
	private function portAttachments(
		mixed $legacy,
		string $snapshot,
		array $messageMap,
		mixed $events,
		mixed $legacyEvents,
		bool $dryRun,
	): int {
		$files = $legacy->table('files')->get()->keyBy('id');
		$attached = 0;

		foreach ($legacy->table('fileables')->orderBy('id')->get() as $row) {
			$file = $files[$row->file_id] ?? null;

			if ($file === null) {
				$this->findings[] = "fileable {$row->id}: file {$row->file_id} missing; skipped";

				continue;
			}

			[$class, $id] = match ($row->fileable_type) {
				'App\Models\Message' => [Message::class, $messageMap[$row->fileable_id] ?? null],
				'App\Models\Event' => [Event::class, ($uuid = $legacyEvents[$row->fileable_id] ?? null) === null ? null : ($events[$uuid] ?? null)],
				default => [null, null],
			};

			if ($id === null) {
				$this->observations[] = "fileable {$row->id}: {$row->fileable_type} {$row->fileable_id} not in the rework; skipped";

				continue;
			}

			$source = $snapshot.'/uploads/'.$file->name;

			if (! is_file($source)) {
				$this->findings[] = "file {$file->id} ({$file->name}): not in the storage snapshot; skipped";

				continue;
			}

			if ($dryRun) {
				$attached++;

				continue;
			}

			Storage::disk('public')->put('uploads/'.$file->name, (string) file_get_contents($source));

			Media::create([
				'uuid' => $file->uuid,
				'mediable_type' => $class,
				'mediable_id' => $id,
				'file' => $file->name,
				'original_name' => $file->original_name,
				'mime_type' => @mime_content_type(Storage::disk('public')->path('uploads/'.$file->name)) ?: null,
				'size' => (int) $file->size,
				'caption' => $file->caption,
				// Not an image, so no dimensions and no crop. `media` holds both
				// kinds; only one of them has a geometry.
				'variant' => 'desktop',
				'sort_order' => 0,
			]);

			$attached++;
		}

		return $attached;
	}

	private function report(int $ported, int $recipients, int $attachments, int $skipped, bool $dryRun): void
	{
		$this->newLine();
		$this->table(['', ''], [
			[$dryRun ? 'Would port messages' : 'Messages ported', $ported],
			['Recipient rows', $recipients],
			['Attachments as media', $attachments],
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
		}
	}
}
