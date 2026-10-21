<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Media;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Puts every Veranstaltung's and message's file on the private `attachments`
 * disk ([[Media::disk]], [[08-accounts]]), wherever it was found in the public
 * `uploads/`.
 *
 * A file lands in the old place whenever storage is copied in from somewhere
 * that predates the private disk — a legacy snapshot, a dev copy rsynced to a
 * server. `port:messages` and uploads write to the right place on their own;
 * this is for everything that arrived some other way, so it is a step of
 * every deploy (`.rework/Deploy.md`). Moves only what is in the old place and
 * not yet in the new one, so it can be run any number of times.
 */
class MoveAttachments extends Command
{
	protected $signature = 'media:move-attachments
		{--back : Return them to the public uploads/ (the migration\'s down)}';

	protected $description = 'Move course documents and message attachments onto the private disk';

	public function handle(): int
	{
		$public = Storage::disk('public');
		$private = Storage::disk('attachments');
		[$from, $to] = $this->option('back') ? [$private, $public] : [$public, $private];

		$moved = 0;
		$missing = [];

		Media::query()->whereIn('mediable_type', [Event::class, Message::class])->each(function (Media $media) use ($from, $to, &$moved, &$missing) {
			[$source, $target] = $this->option('back')
				? [$media->file, 'uploads/'.$media->file]
				: ['uploads/'.$media->file, $media->file];

			if ($from->exists($source) && ! $to->exists($target)) {
				$to->writeStream($target, $from->readStream($source));
				$from->delete($source);
				$moved++;
			}

			if (! $to->exists($target)) {
				$missing[] = "{$media->id} {$media->file}";
			}
		});

		$this->info("{$moved} moved.");

		if ($missing !== []) {
			$this->warn(count($missing).' with no file in either place: '.implode(', ', $missing));
		}

		return self::SUCCESS;
	}
}
