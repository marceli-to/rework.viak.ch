<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Actions\Bookings\CompleteCheckout;
use App\Actions\Bookings\PriceBasket;
use App\Actions\Invoices\RecordCardPayment;
use App\Actions\Licences\PlaceLicenceOrder;
use App\Events\LicenceOrderPlaced;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\StoreAddressRequest;
use App\Http\Requests\Bookings\CompleteCheckoutRequest;
use App\Models\Country;
use App\Support\CheckoutSession;
use App\Support\Payments\PaymentGateway;
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
			'licence_email' => ['nullable', 'email', 'max:255'],
		], [], ['invoice_address' => 'Rechnungsadresse', 'licence_email' => 'E-Mail']);

		$uuid = $validated['invoice_address'] ?? null;
		$address = $uuid === null ? null : $user->addresses()->where('uuid', $uuid)->first();

		if ($uuid !== null && $address === null) {
			return back()->withErrors([
				'invoice_address' => 'Bitte Rechnungsadresse auswählen',
			]);
		}

		CheckoutSession::setInvoiceAddress($address);

		// Posted only when the basket holds a licence (#46). Kept on the
		// account; the account's own address is no answer worth storing.
		if ($request->has('licence_email')) {
			$email = $validated['licence_email'] ?? null;
			$user->forceFill(['licence_email' => $email && strcasecmp($email, $user->email) !== 0 ? $email : null])->save();
		}

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
	 * later ([[03-invoices]]). Licences are paid by card after the summary
	 * ([[complete]]); this step only says so.
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
	/**
	 * **With licences** ([[13-checkout]]): the courses are booked first, as
	 * before, because that is the part that can still be refused (a seat gone,
	 * a code expired); then the licences become an order, invoiced at once
	 * ([[PlaceLicenceOrder]]), and a priced one goes to Stripe's page. A
	 * payment abandoned there leaves the order waiting and the courses booked
	 * (#45): the confirmation offers the payment again, and so does the
	 * account.
	 */
	public function complete(
		CompleteCheckoutRequest $request,
		PriceBasket $price,
		CompleteCheckout $checkout,
		PlaceLicenceOrder $placeOrder,
		PaymentGateway $gateway,
	): RedirectResponse {
		$user = $request->user();
		$address = CheckoutSession::invoiceAddress($user);

		$basket = $price->execute($request->selections(), $request->code(), $user, $request->licences());
		$basket->assertShown($request->totalShown());

		if ($basket->hasCourses()) {
			$checkout->execute(
				user: $user,
				basket: $basket,
				invoiceAddress: $address?->toSnapshot(),
			);
		}

		$order = null;

		if ($basket->hasLicences()) {
			$order = $placeOrder->execute(
				user: $user,
				lines: array_map(fn ($line) => $line->line(), $basket->licences),
				invoiceAddress: $address?->toSnapshot(),
				deliveryEmail: $user->licence_email,
			);

			LicenceOrderPlaced::dispatch($order);
		}

		CheckoutSession::clear();
		CheckoutSession::setOutcome($basket->hasCourses(), $order);

		// Read back, not taken from the order: a fresh invoice carries its
		// status as the database defaulted it, not as a model attribute.
		$invoice = $order?->invoice?->fresh();

		if ($invoice?->isPending()) {
			$back = url(SiteUrl::checkout('confirmation'));

			return redirect()->away($gateway->checkout($invoice, $back.'?session_id={CHECKOUT_SESSION_ID}', $back.'?abgebrochen'));
		}

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
	public function confirmation(Request $request, PaymentGateway $gateway, RecordCardPayment $record): View
	{
		$user = $request->user();

		// Back from Stripe: settled here if paid, as on the invoice's own
		// payment page ([[PaymentController::success]]); the webhook too.
		$payment = $request->filled('session_id') ? $gateway->paidSession((string) $request->query('session_id')) : null;

		if ($payment !== null) {
			$record->execute($payment);
		}

		$order = CheckoutSession::licenceOrder($user);

		return view('site.checkout.confirmation', [
			'booked' => CheckoutSession::booked() || $order === null,
			'order' => $order,
			'paid' => $order?->isPaid() ?? false,
			'confirming' => $request->filled('session_id') && ! ($order?->isPaid() ?? false),
			'email' => $order?->deliveryEmail(),
		]);
	}
}
