{{--
	*Kreditkartenzahlung*, legacy's `web/pages/payment/index.blade.php` and
	`info.blade.php` ([[PaymentController]]), drawn in the checkout steps'
	rows ([[checkout.summary]]): *Schritt 1/1*, a row per line, the total,
	*Weiter zur Zahlung*.

	**Rows from the invoice's lines, not from a booking.** Legacy drew the
	booking's event row (course, dates, place, experts), which a licence
	invoice has none of; a line's frozen description says what was bought for
	both. Settled or cancelled, the page says so instead (legacy's `info`).
	Back from Stripe's *Zurück*, legacy's cancel page's sentence is on top.
--}}
<x-layout.site title="Kreditkartenzahlung" heading="Kreditkartenzahlung" auth>
	@if (! $invoice->isPending())
		<x-layout.article>
			<x-slot:aside>
				<h1 class="hidden font-bold text-teal sm:block">{{ $invoice->isPaid() ? 'Rechnung bezahlt' : 'Rechnung storniert' }}</h1>
			</x-slot:aside>

			@if ($invoice->isPaid())
				<p>Die Rechnung <strong class="font-bold">{{ $invoice->number }}</strong> wurde{{ $invoice->paid_at ? ' am '.$invoice->paid_at->format('d.m.Y') : '' }} beglichen.</p>
			@else
				<p>Die Rechnung <strong class="font-bold">{{ $invoice->number }}</strong> wurde storniert und ist nicht mehr zu bezahlen.</p>
			@endif
		</x-layout.article>
	@else
		<x-layout.article>
			<x-slot:aside>
				<h1 class="hidden font-bold text-teal sm:block">Kreditkartenzahlung</h1>
			</x-slot:aside>

			@if ($cancelled)
				<p>Du hast den Zahlungsvorgang abgebrochen. Brauchst Du Hilfe bei der Zahlung oder gibt es andere Gründe? Kontaktiere uns!</p>
				<p class="mt-16"><a href="tel:+41435014040" class="hover:text-teal">+41 43 501 40 40</a><br><a href="mailto:hallo@visualisierungs-akademie.ch" class="hover:text-teal">hallo@visualisierungs-akademie.ch</a></p>
			@endif
		</x-layout.article>

		<div class="mt-48 lg:mt-64">
			<header class="sm:grid sm:grid-cols-12 sm:gap-16 sm:text-lg lg:gap-40 lg:text-xl">
				<div class="mb-8 sm:col-span-4"><strong class="font-bold">Schritt 1/1</strong></div>
				<div class="mb-8 sm:col-span-8"><h2 class="font-bold">Rechnung {{ $invoice->number }}</h2></div>
			</header>

			@foreach ($invoice->items as $item)
				<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
					<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
						<div class="sm:col-span-8">{{ $item->description }}</div>
						<div class="sm:col-span-4 sm:text-right">CHF {{ $item->total }}</div>
					</div>
				</div>
			@endforeach

			<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-4">
						<strong class="font-bold">Total</strong><br>
						{{ bccomp((string) $invoice->vat, '0', 2) > 0 ? 'inkl.' : 'exkl.' }} {{ config('invoice.vat_label') }}
					</div>
					<div class="sm:col-span-8 sm:text-right">
						<strong class="font-bold">CHF {{ $invoice->grand_total }}</strong>
					</div>
				</div>
			</div>

			<form method="POST" action="{{ \App\Support\SiteUrl::invoicePayment($invoice->uuid) }}">
				@csrf
				<footer class="mt-32 flex justify-end border-t-2 border-gray-400 sm:mt-48">
					<button type="submit"
						class="flex items-center py-12 leading-none transition-colors hover:text-teal sm:py-16 sm:text-xl lg:text-3xl">
						<span class="mr-8 sm:mr-12">Weiter zur Zahlung</span>
						<x-icon.arrow-right />
					</button>
				</footer>
			</form>
		</div>
	@endif
</x-layout.site>
