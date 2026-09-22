<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Actions\Bookings\CompleteCheckout;
use App\Actions\Bookings\PriceBasket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\StoreAddressRequest;
use App\Http\Requests\Bookings\CompleteCheckoutRequest;
use App\Models\Country;
use App\Support\CheckoutSession;
use App\Support\SiteUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The checkout steps that have server state ([[09-public-site]]).
 *
 * The basket does not — it is in the browser, and its page is a `Route::view`.
 * From step 2 on, every screen is Blade with a POST and the answers in the
 * session ([[CheckoutSession]]), which is the shape `00-foundation.md` settled
 * on: the server is the pricing authority, so the wizard is not client-held.
 */
class CheckoutController extends Controller
{
	/**
	 * Step 2 — *Kontakt*: who is attending, and where the bill goes.
	 *
	 * Both blocks are read off the signed-in customer, so there is nothing to
	 * fetch and nothing to render in the browser. Legacy made two API calls
	 * here (`/api/student`, `/api/basket`) to draw a screen whose every value
	 * the server already had.
	 */
	public function address(Request $request): View
	{
		$user = $request->user();

		return view('site.checkout.address', [
			'user' => $user,
			'addresses' => $user->addresses()->with('country')->orderBy('company')->orderBy('last_name')->get(),
			'selected' => CheckoutSession::invoiceAddress($user),
			'countries' => Country::query()->orderBy('order')->orderBy('name')->get(),
		]);
	}

	/**
	 * And the answer.
	 *
	 * An empty `invoice_address` is *entspricht Teilnehmer-Adresse*, which is
	 * the common case and a real answer. A uuid has to be one of the
	 * customer's own — `exists` on the table alone would let a guessed uuid
	 * bill someone else's employer.
	 */
	public function storeAddress(Request $request): RedirectResponse
	{
		$user = $request->user();

		$validated = $request->validate([
			'invoice_address' => ['nullable', 'string', 'uuid'],
		], [], ['invoice_address' => 'Rechnungsadresse']);

		$uuid = $validated['invoice_address'] ?? null;
		$address = $uuid === null ? null : $user->addresses()->where('uuid', $uuid)->first();

		if ($uuid !== null && $address === null) {
			return back()->withErrors([
				'invoice_address' => 'Bitte Rechnungsadresse auswählen',
			]);
		}

		CheckoutSession::setInvoiceAddress($address);

		return redirect(SiteUrl::checkout('payment'));
	}

	/**
	 * *Adresse erfassen* — the dialog on step 2.
	 *
	 * A real form POST rather than legacy's `/api/student/address` call, so the
	 * step works without JavaScript and a validation error comes back the way
	 * every other form on this site handles one. The dialog reopens itself from
	 * `address_form`, because a redirect would otherwise land on a closed
	 * dialog with the errors invisible behind it.
	 *
	 * A new address is selected immediately. Legacy does the same, and it is
	 * the only reason to be typing one here.
	 */
	public function storeNewAddress(StoreAddressRequest $request): RedirectResponse
	{
		$address = $request->user()->addresses()->create($request->validated());

		CheckoutSession::setInvoiceAddress($address);

		return redirect(SiteUrl::checkout('address'));
	}

	/**
	 * Step 3 — *Zahlung*, which takes no payment.
	 *
	 * `Payment.vue` is a paragraph of text and a discount-code field, and that
	 * is the whole step: invoices are raised on `EventConfirmed`, days or weeks
	 * later ([[03-invoices]]), so **there is no Stripe anywhere in this flow**.
	 * The `PaymentController` and its checkout session are a separate thing,
	 * for paying an invoice that already exists.
	 *
	 * Nothing to hand it — the code belongs to the basket, which is the
	 * browser's ([[09-public-site]]).
	 */
	public function payment(): View
	{
		return view('site.checkout.payment');
	}

	/** Step 4 — *Zusammenfassung*. The addresses are the server's; the lines are not. */
	public function summary(Request $request): View
	{
		$user = $request->user();

		return view('site.checkout.summary', [
			'user' => $user,
			'invoiceAddress' => CheckoutSession::invoiceAddress($user),
		]);
	}

	/**
	 * *Buchen*, and the only irreversible thing in the flow.
	 *
	 * A real form post rather than legacy's `POST /api/booking`, so it carries
	 * a CSRF token, ends in a redirect, and a failure lands back on the page
	 * with a message. The selection comes out of hidden inputs the store fills,
	 * because the basket lives in the browser — but **nothing about the price
	 * does**: the server re-prices from scratch, re-checks every seat, and
	 * refuses a checkout whose total has moved since the customer saw it
	 * ([[CompleteCheckout]]).
	 *
	 * The invoice address comes from the session and is snapshotted **here**,
	 * from the row as it is now, so an address edited between step 2 and this
	 * moment bills the edit rather than a stale copy.
	 */
	public function complete(
		CompleteCheckoutRequest $request,
		PriceBasket $price,
		CompleteCheckout $checkout,
	): RedirectResponse {
		$user = $request->user();
		$address = CheckoutSession::invoiceAddress($user);

		$basket = $price->execute($request->selections(), $request->code(), $user);

		$checkout->execute(
			user: $user,
			basket: $basket,
			totalShown: $request->totalShown(),
			invoiceAddress: $address?->toSnapshot(),
		);

		CheckoutSession::clear();

		return redirect(SiteUrl::checkout('confirmation'));
	}

	/**
	 * *Buchung abgeschlossen*.
	 *
	 * The page also tells the browser to empty its basket — the one thing the
	 * server cannot do, because the basket is in `localStorage`. Legacy never
	 * did: `BasketStore` was a session bag and a completed checkout simply
	 * dropped it, which is the same reason nothing recorded that two bookings
	 * had been one purchase ([[06-bookings]]).
	 */
	public function confirmation(): View
	{
		return view('site.checkout.confirmation');
	}
}
