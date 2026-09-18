<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;

/**
 * The next invoice number ([[03-invoices]]).
 *
 * Six digits, one continuous sequence since 2023 — no yearly reset, 569 issued
 * so far — and never reused: a number that has been on a document a customer
 * holds cannot come back, so soft-deleted and cancelled invoices still own
 * theirs.
 *
 * The mechanism moved to [[SequentialNumber]] when chunk 06 gave booking
 * numbers the same treatment, because legacy had this bug three times over and
 * fixing it once per caller is how it comes back.
 */
final class InvoiceNumber extends SequentialNumber
{
	protected function query(): Builder
	{
		return Invoice::withTrashed();
	}
}
