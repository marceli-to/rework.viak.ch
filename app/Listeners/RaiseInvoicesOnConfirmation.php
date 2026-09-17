<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Invoices\RaiseInvoicesForEvent;
use App\Events\EventConfirmed;

/**
 * Turns a confirmation into invoices ([[EventConfirmed]]).
 *
 * Deliberately synchronous. Legacy reached the same outcome by queueing a
 * confirmation *email* whose Mailable created the invoice while rendering
 * itself, so the document existed only once a queue worker got round to the
 * mail — and twice if it ran twice. Raising the invoice is the business of
 * confirming; sending the PDF is the business of the mail. The mail can wait
 * in a queue. The invoice should not.
 */
class RaiseInvoicesOnConfirmation
{
	public function __construct(private readonly RaiseInvoicesForEvent $raise) {}

	public function handle(EventConfirmed $confirmed): void
	{
		$this->raise->execute($confirmed->event);
	}
}
