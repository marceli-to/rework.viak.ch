<?php

declare(strict_types=1);

namespace App\Actions\CompanyCourses;

use App\Actions\Invoices\CancelInvoice;
use App\Enums\CancellationReason;
use App\Models\CompanyCourse;
use RuntimeException;

/**
 * *Rechnung stornieren* on a Firmenkurs ([[16-firmenkurse]]): the invoice is
 * cancelled and reversed in the books as every cancellation is
 * ([[CancelInvoice]]), and the Firmenkurs opens again for a changed one.
 *
 * A paid invoice is not withdrawn: the money would have to go back first,
 * which is a conversation, not a button.
 */
class WithdrawCompanyCourseInvoice
{
	public function __construct(private readonly CancelInvoice $cancel) {}

	public function execute(CompanyCourse $course): CompanyCourse
	{
		$invoice = $course->invoice;

		if ($invoice === null || ! $invoice->isPending()) {
			throw new RuntimeException("Firmenkurs {$course->uuid} has no open invoice to withdraw.");
		}

		$this->cancel->execute($invoice, CancellationReason::Withdrawn);

		return $course->refresh();
	}
}
