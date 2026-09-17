<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * The next invoice number ([[03-invoices]]).
 *
 * Six digits, one continuous sequence since 2023 — no yearly reset, 569 issued
 * so far — and never reused: a number that has been on a document a customer
 * holds cannot come back, so soft-deleted and cancelled invoices still own
 * theirs. Hence `withTrashed()`.
 *
 * Legacy did `InvoiceModel::withTrashed()->get()->last()->number + 1`, which
 * loaded every invoice to read one of them and trusted primary-key order to
 * mean "highest number". `max()` asks the question directly, and the lock
 * closes the window where two confirmations in the same second would mint the
 * same number — a `unique` index would then make one of them fail, which is
 * the right failure but not one anybody should have to see.
 */
final class InvoiceNumber
{
	public const LENGTH = 6;

	public function next(): string
	{
		$highest = (int) Invoice::withTrashed()->lockForUpdate()->max('number');

		return $this->pad($highest + 1);
	}

	public function pad(int $number): string
	{
		return str_pad((string) $number, self::LENGTH, '0', STR_PAD_LEFT);
	}

	/**
	 * Reserves the next number inside the caller's transaction. `lockForUpdate`
	 * only holds inside one, and silently does nothing outside — so this
	 * refuses rather than handing back a number two writers could share.
	 */
	public function nextInTransaction(): string
	{
		if (DB::transactionLevel() === 0) {
			throw new \RuntimeException('Invoice numbers must be minted inside a transaction, or two invoices can take the same one.');
		}

		return $this->next();
	}
}
