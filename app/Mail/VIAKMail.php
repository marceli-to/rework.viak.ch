<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * What every VIAK mail shares ([[10-mail]]).
 *
 * - **Queued**, so a checkout or a dashboard click never waits on SMTP; the
 *   worker runs from cron ([[00-foundation]]).
 * - **After the commit**: bookings are written inside a transaction, and a
 *   checkout that rolls back must not have sent its confirmation.
 * - The sender is `config('mail.from')`, which Laravel applies to every mail;
 *   legacy's 24 classes each read `env()` for it, which a cached config breaks.
 * - Outside production every one goes to the catch-all ([[AppServiceProvider]]).
 *
 * **A public property reaches the view under its own name, and wins** over
 * what `content()` passes: a formatted `amount` was overwritten by the raw one.
 * A view key that formats a property takes another name (`cost`).
 */
abstract class VIAKMail extends Mailable implements ShouldQueue
{
	use Queueable;
	use SerializesModels;

	public function __construct()
	{
		$this->afterCommit();
	}
}
