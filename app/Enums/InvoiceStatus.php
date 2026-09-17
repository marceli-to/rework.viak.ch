<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where an invoice stands ([[03-invoices]]).
 *
 * The four legacy values, kept verbatim in the database as uppercase strings
 * so that ported rows and Run My Accounts keep reading the same way. In the
 * 2026-09-11 dump: 541 PAID, 16 CANCELLED, 9 OPEN, 3 OVERDUE.
 *
 * OVERDUE is not a fifth state so much as OPEN past its `due_at`. It is stored
 * rather than derived because the legacy nightly task writes it and Run My
 * Accounts reports it — and because `due_at` was, until this rework, a column
 * that rewrote itself (see the migration), so a derived answer would have been
 * wrong on every row.
 */
enum InvoiceStatus: string
{
	case Open = 'OPEN';
	case Paid = 'PAID';
	case Overdue = 'OVERDUE';
	case Cancelled = 'CANCELLED';

	/** Still owed: nobody has paid it and nobody has called it off. */
	public function isPending(): bool
	{
		return $this === self::Open || $this === self::Overdue;
	}

	/**
	 * Settled one way or the other. A paid invoice must not be reissued and a
	 * cancelled one must not be resurrected, which is the same question asked
	 * twice — hence one method.
	 */
	public function isSettled(): bool
	{
		return ! $this->isPending();
	}
}
