<x-layout.site title="Mein Warenkorb" auth>
	{{--
		Step 4 of 4, from `frontend/checkout/views/Summary.vue`
		([[09-public-site]]).

		**Half server, half browser, and the split is not arbitrary.** The two
		addresses are the server's — one is the customer's own, the other is the
		answer step 2 put in the session — so they are rendered here. The lines
		are the browser's, because the selection is, so they come back from
		`/api/basket/price` like they do on the basket page.

		What is posted is only the selection and the total the customer was
		shown. **Nothing about the price crosses this boundary:**
		[[CompleteCheckout]] re-prices from scratch, re-checks every seat, and
		refuses a checkout whose total has moved.
	--}}
	<article class="relative">
		<aside>
			<h1 class="hidden font-bold text-teal sm:block">Mein Warenkorb</h1>
		</aside>
	</article>

	{{-- The three ways this can legitimately fail, all 422 from the Actions and
	     all carrying a sentence the customer is meant to read: a code that
	     expired, a price that moved while the basket sat open, or the last seat
	     going. Legacy's equivalents failed silently ([[06-bookings]]). --}}
	@if ($errors->any())
		<x-ui.toast>{{ $errors->first() }}</x-ui.toast>
	@endif

	<div class="mt-48 lg:mt-64" x-data="basketList">
		<header class="sm:grid sm:grid-cols-12 sm:gap-16 sm:text-lg lg:gap-40 lg:text-xl">
			<div class="mb-8 sm:col-span-4"><strong class="font-bold">Schritt 4/4</strong></div>
			<div class="mb-8 sm:col-span-8"><h2 class="font-bold">Zusammenfassung</h2></div>
		</header>

		{{-- `Adresse` in `span-4`, and the invoice address beside it in `span-8`
		     only when there is one — `mt-3x` below `sm`, where they stack. --}}
		<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-4">
					<strong class="font-bold">Adresse</strong>
					<div>
						@foreach ($user->addressLines() as $line)
							{{ $line }}@if (! $loop->last)<br>@endif
						@endforeach
					</div>
				</div>

				@if ($invoiceAddress)
					<div class="mt-12 sm:col-span-8 sm:mt-0">
						<strong class="font-bold">Rechnungsadresse</strong>
						<div>
							@foreach ($invoiceAddress->lines() as $line)
								{{ $line }}@if (! $loop->last)<br>@endif
							@endforeach
						</div>
					</div>
				@endif
			</div>
		</div>

		{{-- The same row as step 1, without its *Entfernen*: legacy renders the
		     same `StackedListEvent` here with the `action` slot unfilled. --}}
		<template x-for="item in items" :key="item.event.uuid">
			<x-row.basket />
		</template>

		{{--
			The money.

			Legacy shows *Zwischentotal* and the code only when a discount
			applies, and a *Total* row labelled **exkl. Mehrwertsteuer** —
			because `getTotals()` hard-zeroes VAT under a `@todo: fix vat on
			event`, which chunk 03 found was wrong about its own zero.

			**A VAT row is the one departure on this page**, and it appears only
			when there is VAT to show. Courses are exempt, so that is exactly
			when a laptop is rented — every other basket renders precisely
			legacy's rows. The alternative was quoting a total the customer is
			not charged, and `total_shown` is checked against the figure they
			actually pay ([[CompleteCheckout]]), so a net total would have been
			refused by our own guard. The client's own confirmed pattern is
			`Gesamtnettosumme` → `zzgl. 8.1 % MwSt.` → `Gesamtsumme`
			([[03-invoices]]).
		--}}
		<template x-if="pricing && (pricing.discount > 0 || pricing.vat > 0)">
			<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-4">Zwischentotal</div>
					<div class="sm:col-span-8 sm:text-right" x-text="`CHF ${pricing.net}`"></div>
				</div>
			</div>
		</template>

		<template x-if="pricing && pricing.discount > 0">
			<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-4">
						Gutschein-Code <strong class="font-bold" x-text="pricing.discount_code"></strong>
					</div>
					<div class="sm:col-span-8 sm:text-right" x-text="`– CHF ${pricing.discount}`"></div>
				</div>
			</div>
		</template>

		<template x-if="pricing && pricing.vat > 0">
			<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-4">MwSt. {{ config('invoice.vat_rate') }} % auf Mietcomputer</div>
					<div class="sm:col-span-8 sm:text-right" x-text="`CHF ${pricing.vat}`"></div>
				</div>
			</div>
		</template>

		<template x-if="pricing && pricing.total > 0">
			<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-4">
						<strong class="font-bold">Total</strong><br>
						{{-- Both labels are true. Without a rental nothing on the
						     basket is taxed, which is legacy's sentence; with one
						     the total already carries its VAT. --}}
						<span x-text="pricing.vat > 0 ? 'inkl. Mehrwertsteuer' : 'exkl. Mehrwertsteuer'"></span>
					</div>
					<div class="sm:col-span-8 sm:text-right">
						<strong class="font-bold" x-text="`CHF ${pricing.total}`"></strong>
					</div>
				</div>
			</div>
		</template>

		{{--
			*Buchen*, as a real form post.

			Legacy sends `POST /api/booking` and then sets
			`window.location.href`. A form means a CSRF token, a redirect, and a
			failure that lands back here with a message the customer can read.

			The hidden inputs are the browser's basket, written out by Alpine —
			the only way a server-rendered form can carry a selection the server
			does not hold. `total_shown` is not a price the server accepts: it
			is what the page claims to have displayed, and the checkout is
			refused if the real total has moved since.
		--}}
		<form method="POST" action="{{ \App\Support\SiteUrl::checkout('summary') }}"
			x-show="items.length" x-cloak>
			@csrf

			<template x-for="(item, i) in $store.basket.items" :key="item.event">
				<span>
					<input type="hidden" :name="`items[${i}][event]`" :value="item.event">
					<input type="hidden" :name="`items[${i}][rental]`" :value="item.rental ? 1 : 0">
				</span>
			</template>

			<input type="hidden" name="code" :value="$store.basket.code ?? ''">
			<input type="hidden" name="total_shown" :value="pricing?.total ?? ''">

			<footer class="mt-32 grid grid-cols-12 gap-16 border-t-2 border-gray-400 sm:mt-48 lg:gap-40">
				<div class="col-span-6 flex justify-start">
					<a href="{{ \App\Support\SiteUrl::checkout('payment') }}"
						class="flex items-center py-12 leading-none transition-colors hover:text-teal sm:py-16 sm:text-xl lg:text-3xl">
						<x-icon.arrow-left />
						<span class="ml-8 sm:ml-12">Zurück</span>
					</a>
				</div>
				<div class="col-span-6 flex justify-end">
					<button type="submit" :disabled="! pricing"
						class="flex items-center py-12 leading-none transition-colors hover:text-teal disabled:opacity-60 sm:py-16 sm:text-xl lg:text-3xl">
						<span class="mr-8 sm:mr-12">Buchen</span>
						<x-icon.arrow-right />
					</button>
				</div>
			</footer>
		</form>

		<template x-if="settled && ! items.length">
			<div class="mt-12 sm:mt-24 lg:mt-36">Dein Warenkorb ist leer...</div>
		</template>
	</div>
</x-layout.site>
