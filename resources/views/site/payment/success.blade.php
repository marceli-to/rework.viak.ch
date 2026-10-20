{{--
	*Zahlung erfolgreich* (Marcel, 2026-10-20; legacy says
	*Kreditkartenzahlung erfolgreich*), legacy's
	`web/pages/payment/success.blade.php`, its sentence as it is
	([[PaymentController::success]]). Without an invoice, Stripe has not
	reported the payment yet: the webhook settles it, and the mail confirms.
--}}
<x-layout.site title="Zahlung erfolgreich" heading="Zahlung erfolgreich" auth>
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Zahlung erfolgreich</h1>
		</x-slot:aside>

		@if ($invoice)
			<p>Wir haben deine Zahlung für die Rechnung Nr. {{ $invoice->number }} erhalten. Vielen Dank.</p>
		@else
			<p>Vielen Dank. Sobald deine Zahlung bestätigt ist, erhältst du eine Bestätigung per E-Mail.</p>
		@endif
	</x-layout.article>
</x-layout.site>
