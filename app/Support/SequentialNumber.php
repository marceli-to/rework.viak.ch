<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * A zero-padded document number that never repeats and never goes backwards.
 *
 * Extracted so [[InvoiceNumber]] and [[BookingNumber]] share the fix rather
 * than the bug. Legacy had the same three lines twice over in
 * `Invoice::getNumber()`, `RentalInvoice::getNumber()` and
 * `Booking::getNumber()`:
 *
 *     $rows = Model::withTrashed()->get();
 *     $number = (int) $rows->last()->number + 1;
 *
 * which loads every row to read one of them, and trusts primary-key order to
 * mean "highest number" — two different claims that happen to agree until a row
 * is inserted out of order. `max()` asks the question it actually means.
 *
 * `withTrashed()` is not an oversight. A number that has been on a document a
 * customer holds cannot come back, so soft-deleted and cancelled rows keep
 * theirs.
 */
abstract class SequentialNumber
{
	public const LENGTH = 6;

	/** A query over every row that has ever held a number, including trashed. */
	abstract protected function query(): Builder;

	public function next(): string
	{
		$highest = (int) $this->query()->lockForUpdate()->max('number');

		return $this->pad($highest + 1);
	}

	public function pad(int $number): string
	{
		return str_pad((string) $number, static::LENGTH, '0', STR_PAD_LEFT);
	}

	/**
	 * Reserves the next number inside the caller's transaction.
	 *
	 * `lockForUpdate` only holds inside one and silently does nothing outside,
	 * so this refuses rather than handing back a number two writers could share.
	 * The `unique` index would then make one of them fail, which is the right
	 * failure but not one anybody should have to see.
	 */
	public function nextInTransaction(): string
	{
		if (DB::transactionLevel() === 0) {
			throw new RuntimeException(static::class.' must be minted inside a transaction, or two rows can take the same number.');
		}

		return $this->next();
	}
}
