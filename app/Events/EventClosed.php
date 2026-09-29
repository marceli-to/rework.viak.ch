<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;

/** A course date that has run was closed ([[SetEventState]]); its attendees are confirmed ([[10-mail]]). */
class EventClosed
{
	use Dispatchable;

	public function __construct(public readonly Event $event) {}
}
