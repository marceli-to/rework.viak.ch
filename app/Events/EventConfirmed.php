<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An event has the numbers and is going ahead ([[SetEventState]]).
 *
 * The single most consequential moment in the domain: it is what tells the
 * students the course is running, and it is what makes their seats billable
 * ([[03-invoices]]). Modelled as an event rather than a direct call because
 * more than one thing has to happen — invoices now, confirmation emails and
 * their PDF attachments when that chunk lands — and because confirming a
 * course should not have to know the list.
 */
class EventConfirmed
{
	use Dispatchable;

	public function __construct(public readonly Event $event) {}
}
