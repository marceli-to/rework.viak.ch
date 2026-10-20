<?php

declare(strict_types=1);

namespace App\Actions\Licences;

use App\Actions\Invoices\CancelInvoice;
use App\Enums\CancellationReason;
use App\Models\LicenceOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cancels a licence order ([[05-licences]], Marcel 2026-10-20): **only while
 * nothing on it has been sent**, since a licence forwarded cannot be taken
 * back, paid or not.
 *
 * The order leaves the worklist, and its invoice is cancelled and reversed in
 * the books ([[CancelInvoice]]). A paid one is cancelled too: the money goes
 * back by hand, as with a course, and the storno stays open in the books
 * until it has.
 */
class CancelLicenceOrder
{
	public function __construct(private readonly CancelInvoice $cancelInvoice) {}

	public function execute(LicenceOrder $order, User $by): LicenceOrder
	{
		return DB::transaction(function () use ($order, $by): LicenceOrder {
			$order = LicenceOrder::query()->lockForUpdate()->with(['items', 'invoice'])->findOrFail($order->id);

			if ($order->isCancelled()) {
				return $order;
			}

			if ($order->items->contains(fn ($item) => $item->dispatched_at !== null)) {
				throw ValidationException::withMessages([
					'order' => 'Die Bestellung kann nicht storniert werden, es wurde bereits etwas versendet.',
				]);
			}

			$order->forceFill(['cancelled_at' => now(), 'cancelled_by' => $by->id])->save();

			if ($order->invoice) {
				$this->cancelInvoice->execute($order->invoice, CancellationReason::OrderCancelled);
			}

			return $order;
		});
	}
}
