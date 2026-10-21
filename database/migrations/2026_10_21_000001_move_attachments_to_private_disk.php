<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Media;
use App\Models\Message;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * The files already on a Veranstaltung or a message, out of the public
 * `uploads/` and onto the private `attachments` disk ([[Media::disk]],
 * [[08-accounts]]). Data, not schema: a database ported before 2026-10-21
 * has them in the old place. A file already moved, or never there, is left
 * alone, so it runs safely on any copy.
 */
return new class extends Migration
{
	public function up(): void
	{
		$this->move(Storage::disk('public'), Storage::disk('attachments'), fn (Media $media) => ['uploads/'.$media->file, $media->file]);
	}

	public function down(): void
	{
		$this->move(Storage::disk('attachments'), Storage::disk('public'), fn (Media $media) => [$media->file, 'uploads/'.$media->file]);
	}

	private function move($from, $to, Closure $paths): void
	{
		Media::query()->whereIn('mediable_type', [Event::class, Message::class])->each(function (Media $media) use ($from, $to, $paths) {
			[$source, $target] = $paths($media);

			if (! $from->exists($source) || $to->exists($target)) {
				return;
			}

			$to->writeStream($target, $from->readStream($source));
			$from->delete($source);
		});
	}
};
