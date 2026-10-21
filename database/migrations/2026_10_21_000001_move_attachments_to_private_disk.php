<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * The files already on a Veranstaltung or a message, out of the public
 * `uploads/` and onto the private `attachments` disk ([[MoveAttachments]],
 * [[08-accounts]]). Data, not schema: a database ported before 2026-10-21
 * has them in the old place. Runs once per database; the command is the
 * deploy step that runs every time (`.rework/Deploy.md`).
 */
return new class extends Migration
{
	public function up(): void
	{
		Artisan::call('media:move-attachments');
	}

	public function down(): void
	{
		Artisan::call('media:move-attachments', ['--back' => true]);
	}
};
