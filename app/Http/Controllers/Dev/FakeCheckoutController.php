<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Support\Payments\FakePaymentGateway;
use App\Support\Payments\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Stripe's page, played ([[FakePaymentGateway]]): the amount, *Bezahlen* and
 * *Abbrechen*, and back to the site the way Stripe sends people back. Only
 * routed outside production, and only answers while the fake is bound.
 */
class FakeCheckoutController extends Controller
{
	public function show(PaymentGateway $gateway, string $session): View
	{
		return view('dev.payment', ['id' => $session, 'session' => $this->session($gateway, $session)]);
	}

	public function update(Request $request, PaymentGateway $gateway, string $session): RedirectResponse
	{
		$found = $this->session($gateway, $session);

		if ($request->input('action') === 'pay') {
			$gateway->pay($session);

			return redirect()->away($found['success']);
		}

		return redirect()->away($found['cancel']);
	}

	/** @return array{invoice: string, number: string, amount: int, success: string, cancel: string, paid: bool} */
	private function session(PaymentGateway $gateway, string $session): array
	{
		abort_unless($gateway instanceof FakePaymentGateway, 404);

		return $gateway->session($session) ?? abort(404);
	}
}
