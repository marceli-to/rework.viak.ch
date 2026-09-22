<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Session;

/**
 * What the checkout remembers between steps ([[09-public-site]]).
 *
 * `00-foundation.md` settled the flow as **a POST per step with the state in
 * the session**, and this is that state — everything the customer has decided
 * that is not in the basket itself. The basket stays in the browser, because
 * legacy's server-held one is why a completed checkout left nothing behind
 * ([[06-bookings]]); what goes here is only the answers the later steps need to
 * ask for again.
 *
 * **It holds a uuid, not an address.** The snapshot frozen onto the booking is
 * built at the last step from the row as it is then, so editing an address
 * between step 2 and step 4 cannot leave the session quoting a stale copy. It
 * also keeps a shape question out of the middle of the flow: `UserAddress` and
 * [[CompleteCheckoutRequest]] currently disagree about what a frozen address
 * looks like, and step 4 is where that gets settled.
 *
 * Nothing here is trusted on its own. Every read re-checks the row still
 * belongs to the customer, because a session outlives the address it names —
 * deleting an invoice address in another tab would otherwise bill the next
 * checkout to a row the customer no longer has.
 */
final class CheckoutSession
{
	private const INVOICE_ADDRESS = 'checkout.invoice_address_uuid';

	/**
	 * The chosen invoice address, or null for *entspricht Teilnehmer-Adresse*.
	 *
	 * Null is a real answer here, not a missing one: legacy's checkbox defaults
	 * to checked and most bookings are billed to the student themselves — 126 of
	 * 710 use a separate address.
	 */
	public static function invoiceAddress(User $user): ?UserAddress
	{
		$uuid = Session::get(self::INVOICE_ADDRESS);

		if (! is_string($uuid)) {
			return null;
		}

		return $user->addresses()->where('uuid', $uuid)->first();
	}

	public static function setInvoiceAddress(?UserAddress $address): void
	{
		$address === null
			? Session::forget(self::INVOICE_ADDRESS)
			: Session::put(self::INVOICE_ADDRESS, $address->uuid);
	}

	/** Called once the checkout has completed, so the next one starts clean. */
	public static function clear(): void
	{
		Session::forget(self::INVOICE_ADDRESS);
	}
}
