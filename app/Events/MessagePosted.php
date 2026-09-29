<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An expert or admin posted to a course date ([[PostMessage]]). Its recipients
 * are already frozen onto the message; the mails read them ([[10-mail]]).
 */
class MessagePosted
{
	use Dispatchable;

	public function __construct(public readonly Message $message) {}
}
